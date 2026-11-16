<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_dashboard_shows_inventory_metrics(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata critica', 'stock' => 2, 'min_stock' => 5, 'price' => 100]);
        Product::factory()->for($category)->create(['name' => 'Pieza sana', 'stock' => 30, 'min_stock' => 5, 'price' => 200]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Resumen del negocio');
        $response->assertSee('Reposición prioritaria');
        $response->assertSee('Más vendidos');
        $response->assertSee('Productos por completar');
        $response->assertSee('Destacados de la home');
        $response->assertSee('Balata critica');
        $response->assertDontSee('Inventario por categoría');
    }

    public function test_admin_products_can_be_filtered_by_low_stock(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata critica', 'stock' => 2, 'min_stock' => 5]);
        Product::factory()->for($category)->create(['name' => 'Pieza sana', 'stock' => 30, 'min_stock' => 5]);

        $response = $this->actingAs($this->admin())->get('/admin/productos?stock=bajo');

        $response->assertOk();
        $response->assertViewIs('admin.products.index');
        $response->assertSee('Balata critica');
        $response->assertDontSee('Pieza sana');
    }

    public function test_admin_products_can_be_searched_by_sku(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata de prueba', 'sku' => 'BAL-TEST-01']);
        Product::factory()->for($category)->create(['name' => 'Disco de prueba', 'sku' => 'DIS-TEST-02']);

        $response = $this->actingAs($this->admin())->get('/admin/productos?search=BAL-TEST-01');

        $response->assertOk();
        $response->assertSee('Balata de prueba');
        $response->assertDontSee('Disco de prueba');
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_admin_dashboard(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get('/admin')->assertForbidden();
    }

    public function test_dashboard_escapes_product_names_in_visible_content_and_links(): void
    {
        $unsafeName = '<script>alert("producto")</script>';
        $product = Product::factory()->create([
            'name' => $unsafeName,
            'stock' => 0,
        ]);
        HomeCard::factory()->create(['title' => $unsafeName, 'product_id' => $product->id]);

        $response = $this->actingAs($this->admin())->get('/admin');
        $home = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($unsafeName);
        $response->assertDontSee($unsafeName, false);
        $response->assertSee(route('admin.products.edit', $product), false);

        $home->assertOk();
        $home->assertSee($unsafeName);
        $home->assertDontSee($unsafeName, false);
        $home->assertSee(route('catalog.show', $product), false);
    }

    public function test_dashboard_counts_payments_and_approved_refunds_by_their_business_dates(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10)->setTime(14, 30));
        $admin = $this->admin();
        $previousSale = $this->sale($admin, ['paid_at' => now()->subDays(40), 'total' => 200]);
        $this->sale($admin, [
            'created_at' => now()->subDays(2),
            'paid_at' => now()->startOfDay(),
            'total' => 125.50,
            'amount_paid' => 200,
            'change_amount' => 74.50,
        ]);
        $cashier = User::factory()->create()->assignRole('empleado');
        $this->sale($cashier, ['paid_at' => now(), 'total' => 74.75, 'payment_method' => 'card']);
        $this->sale($admin, ['paid_at' => now()->startOfDay()->subSecond(), 'total' => 500]);
        $this->sale($admin, ['paid_at' => now()->addSecond(), 'total' => 600]);
        $this->sale($admin, ['paid_at' => null, 'total' => 700]);
        $this->sale($admin, ['status' => Sale::STATUS_PENDING, 'total' => 800]);
        $this->sale($admin, ['status' => Sale::STATUS_CANCELLED, 'total' => 900]);

        $refund = $previousSale->returns()->create([
            'requested_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => SaleReturn::STATUS_APPROVED,
            'reason' => 'Devolución de venta anterior',
            'total' => 35.25,
            'approved_at' => now()->startOfDay(),
        ]);
        $refund->forceFill(['created_at' => now()->subDays(2)])->save();
        foreach ([
            [SaleReturn::STATUS_APPROVED, now(), 10.50],
            [SaleReturn::STATUS_APPROVED, now()->startOfDay()->subSecond(), 100],
            [SaleReturn::STATUS_APPROVED, now()->addSecond(), 200],
            [SaleReturn::STATUS_APPROVED, null, 300],
            [SaleReturn::STATUS_PENDING, now(), 400],
            [SaleReturn::STATUS_REJECTED, now(), 500],
        ] as [$status, $approvedAt, $total]) {
            $previousSale->returns()->create([
                'requested_by' => $admin->id,
                'approved_by' => $admin->id,
                'status' => $status,
                'reason' => 'Prueba del periodo de aprobación',
                'total' => $total,
                'approved_at' => $approvedAt,
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $summary = $response->viewData('salesSummary');
        $this->assertSame(200.25, (float) $summary['paid_total']);
        $this->assertSame(2, $summary['tickets']);
        $this->assertSame(45.75, (float) $summary['refunds_total']);
        $this->assertSame(2, $summary['refunds_count']);
        $this->assertTrue(now()->equalTo($response->viewData('generatedAt')));
    }

    public function test_dashboard_prioritizes_only_active_tracked_stock_using_each_product_minimum(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->count(10)->sequence(
            ['name' => 'Agotado B', 'stock' => 0, 'min_stock' => 0],
            ['name' => 'Agotado A', 'stock' => 0, 'min_stock' => 10],
            ['name' => 'Bajo B', 'stock' => 2, 'min_stock' => 2],
            ['name' => 'Bajo A', 'stock' => 2, 'min_stock' => 3],
            ['name' => 'Fallback', 'stock' => 5, 'min_stock' => 0],
            ['name' => 'Minimo propio', 'stock' => 8, 'min_stock' => 8],
            ['name' => 'Fuera del limite', 'stock' => 9, 'min_stock' => 10],
            ['name' => 'Sano con minimo bajo', 'stock' => 3, 'min_stock' => 2],
            ['name' => 'Sano con fallback', 'stock' => 6, 'min_stock' => 0],
            ['name' => 'Sano', 'stock' => 30, 'min_stock' => 5],
        )->create();
        Product::factory()->for($category)->create(['stock' => 0, 'track_inventory' => false]);
        Product::factory()->for($category)->create(['stock' => 1, 'min_stock' => 5, 'track_inventory' => false]);
        Product::factory()->for($category)->create(['stock' => 0, 'is_active' => false]);
        Product::factory()->for($category)->create(['stock' => 1, 'min_stock' => 5, 'is_active' => false]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $counts = $response->viewData('inventoryCounts');
        $this->assertSame(5, $counts['low_stock']);
        $this->assertSame(2, $counts['out_of_stock']);
        $this->assertSame([
            'Agotado A', 'Agotado B', 'Bajo A', 'Bajo B', 'Fallback', 'Minimo propio',
        ], $response->viewData('lowStockProducts')->pluck('name')->all());
    }

    public function test_dashboard_counts_catalog_issues_only_for_active_products(): void
    {
        Product::factory()->count(7)->sequence(
            ['image_path' => null, 'price' => 10],
            ['image_path' => '', 'price' => 10],
            ['image_path' => '   ', 'price' => 10],
            ['image_path' => 'products/zero.jpg', 'price' => 0],
            ['image_path' => 'products/negative.jpg', 'price' => -1],
            ['image_path' => 'products/valid.jpg', 'price' => 0.01],
            ['image_path' => null, 'price' => 0],
        )->create();
        Product::factory()->create(['is_active' => false, 'image_path' => null, 'price' => 0]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $counts = $response->viewData('catalogCounts');
        $this->assertSame(7, $counts['active']);
        $this->assertSame(4, $counts['missing_photo']);
        $this->assertSame(3, $counts['missing_price']);
    }

    public function test_dashboard_shows_the_ordered_product_cards_used_by_home(): void
    {
        $this->freezeTime();
        $third = HomeCard::factory()->create(['title' => 'Tercera', 'sort_order' => 3]);
        $first = HomeCard::factory()->create(['title' => 'Primera', 'sort_order' => 1]);
        $second = HomeCard::factory()->create(['title' => 'Segunda', 'sort_order' => 1]);
        HomeCard::factory()->create(['title' => 'Oculta', 'sort_order' => 0, 'is_active' => false]);
        HomeCard::factory()->category()->create(['title' => 'Categoría']);

        $response = $this->actingAs($this->admin())->get('/admin');
        $home = $this->get('/');

        $response->assertOk();
        $home->assertOk();
        $expected = [$first->id, $second->id, $third->id];
        $this->assertSame($expected, $response->viewData('homeCards')->modelKeys());
        $this->assertSame($expected, $home->viewData('productCards')->modelKeys());
        $response->assertSeeInOrder([$first->title, $second->title, $third->title]);
        $response->assertDontSee('Oculta');
        $this->assertCount(0, $response->viewData('topProducts'));
        $response->assertSee('Sin ventas de refacciones activas en este periodo.');
    }

    public function test_dashboard_and_home_hide_product_cards_when_the_home_products_section_is_disabled(): void
    {
        HomePage::query()->create(['content' => ['show_products' => false]]);
        HomeCard::factory()->create(['title' => 'Card visible']);
        Product::factory()->create();

        $response = $this->actingAs($this->admin())->get('/admin');
        $home = $this->get(route('home'));

        $response->assertOk();
        $home->assertOk();
        $this->assertCount(0, $response->viewData('homeCards'));
        $this->assertCount(0, $home->viewData('productCards'));
        $this->assertSame(1, $response->viewData('catalogCounts')['active']);
    }

    public function test_dashboard_ranks_five_products_by_gross_paid_units_in_the_last_thirty_calendar_days(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10)->setTime(14, 30));
        $admin = $this->admin();
        $products = Product::factory()->count(6)->sequence(
            ['name' => 'Lider'], ['name' => 'Empate A'], ['name' => 'Empate B'],
            ['name' => 'Cuarto'], ['name' => 'Quinto'], ['name' => 'Sexto'],
        )->create(['featured' => false]);
        $sale = $this->sale($admin, ['paid_at' => now()]);
        foreach ([6, 8, 8, 7, 6, 5] as $index => $quantity) {
            $this->saleItem($sale, $products[$index], $quantity);
        }
        $boundarySale = $this->sale($admin, [
            'created_at' => now()->subDays(40),
            'paid_at' => now()->startOfDay()->subDays(29),
        ]);
        $returnedItem = $this->saleItem($boundarySale, $products[0], 4);
        $returnedItem->update(['returned_quantity' => 4]);
        $return = $boundarySale->returns()->create([
            'requested_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => SaleReturn::STATUS_APPROVED,
            'reason' => 'No descontar del ranking bruto',
            'total' => 40,
            'approved_at' => now(),
        ]);
        $return->items()->create([
            'sale_item_id' => $returnedItem->id,
            'quantity' => 4,
            'unit_price' => 10,
            'line_total' => 40,
        ]);
        foreach ([
            ['paid_at' => now()->startOfDay()->subDays(29)->subSecond()],
            ['paid_at' => now()->addSecond()],
            ['paid_at' => null],
            ['status' => Sale::STATUS_PENDING],
            ['status' => Sale::STATUS_CANCELLED],
        ] as $attributes) {
            $this->saleItem($this->sale($admin, $attributes), $products[5], 100);
        }
        $inactive = Product::factory()->create(['is_active' => false]);
        $this->saleItem($sale, $inactive, 100);
        $this->saleItem($sale, $products[5], 100)->update(['is_custom' => true]);
        $sale->items()->create([
            'product_id' => null,
            'is_custom' => true,
            'custom_name' => 'Producto libre',
            'product_name' => 'Producto libre',
            'quantity' => 200,
            'unit_price' => 10,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $topProducts = $response->viewData('topProducts');
        $this->assertSame($products->take(5)->modelKeys(), $topProducts->modelKeys());
        $this->assertSame([10, 8, 8, 7, 6], $topProducts->map(fn (Product $product): int => (int) $product->units_sold)->all());
    }

    public function test_dashboard_renders_empty_business_sections_without_invented_data(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $summary = $response->viewData('salesSummary');
        $this->assertSame(0.0, (float) $summary['paid_total']);
        $this->assertSame(0, $summary['tickets']);
        $this->assertSame(0.0, (float) $summary['refunds_total']);
        $this->assertSame(0, $summary['refunds_count']);
        $this->assertSame(['low_stock' => 0, 'out_of_stock' => 0], $response->viewData('inventoryCounts'));
        $this->assertSame(['active' => 0, 'missing_photo' => 0, 'missing_price' => 0], $response->viewData('catalogCounts'));
        $this->assertCount(0, $response->viewData('lowStockProducts'));
        $this->assertCount(0, $response->viewData('homeCards'));
        $this->assertCount(0, $response->viewData('topProducts'));
        $response->assertSee('Sin reposiciones pendientes');
        $response->assertSee('Sin ventas de refacciones activas en este periodo.');
        $response->assertSee('No hay cards de productos destacados en la home.');
        $response->assertSee('Aún no hay productos publicados.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function sale(User $user, array $attributes = []): Sale
    {
        return Sale::unguarded(fn (): Sale => Sale::query()->create(array_merge([
            'user_id' => $user->id,
            'status' => Sale::STATUS_PAID,
            'idempotency_key' => (string) Str::uuid(),
            'total' => 100,
            'paid_at' => now(),
        ], $attributes)));
    }

    private function saleItem(Sale $sale, Product $product, int $quantity): SaleItem
    {
        return $sale->items()->create([
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => 10,
            'line_total' => $quantity * 10,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
