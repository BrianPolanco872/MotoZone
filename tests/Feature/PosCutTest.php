<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function employeeWithCut(array $permissions = []): User
    {
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo(array_merge(['pos.access', 'pos.sell', 'pos.cut.manage'], $permissions));

        return $user;
    }

    private function saleProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'name' => 'Producto turno',
            'sku' => 'TURNO-'.random_int(1000, 9999),
            'barcode' => '750'.random_int(1000000000, 9999999999),
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 10,
            'min_stock' => 2,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ], $overrides));
    }

    private function salePayload(Product $product, string $key): array
    {
        return [
            'idempotency_key' => $key,
            'status' => 'paid',
            'amount_paid' => 100.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ];
    }

    private function makePaidSale(User $user, float $total, string $key): Sale
    {
        $product = Product::factory()->create([
            'name' => 'Producto corte',
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 100,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ]);

        return Sale::create([
            'user_id' => $user->id,
            'folio' => 'V-'.str_pad((string) random_int(1000, 9999), 6, '0', STR_PAD_LEFT),
            'status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => round($total / 1.16, 2),
            'tax_total' => round($total - $total / 1.16, 2),
            'total' => $total,
            'amount_paid' => $total,
            'change_amount' => 0.00,
            'idempotency_key' => $key,
            'paid_at' => now(),
        ]);
    }

    public function test_global_cut_includes_all_employees_sales(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $employeeA = User::factory()->create()->assignRole('empleado');
        $employeeA->givePermissionTo(['pos.access', 'pos.sell']);
        $employeeB = User::factory()->create()->assignRole('empleado');
        $employeeB->givePermissionTo(['pos.access', 'pos.sell']);

        $this->makePaidSale($employeeA, 200.00, 'cut-a-1');
        $this->makePaidSale($employeeB, 150.00, 'cut-a-2');

        CashMovement::create([
            'user_id' => $employeeA->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('$350.00'); // Ventas totales
        $response->assertSee('$500.00'); // Dinero inicial
    }

    public function test_cut_can_be_generated_and_persisted(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);

        $this->makePaidSale($employee, 200.00, 'cut-gen-1');

        $response = $this->actingAs($employee)->post(route('pos.cuts.store'), [
            'date' => now()->toDateString(),
            'notes' => 'Corte de prueba',
        ]);

        $response->assertRedirect();

        $cut = DailyCut::query()
            ->where('scope', DailyCut::SCOPE_EMPLOYEE)
            ->where('user_id', $employee->id)
            ->whereDate('cut_date', now()->toDateString())
            ->first();

        $this->assertNotNull($cut);
        $this->assertSame('Corte de prueba', $cut->notes);
    }

    public function test_employee_can_see_own_cut(): void
    {
        $employee = $this->employeeWithCut(['pos.sales.day.own']);

        $this->makePaidSale($employee, 100.00, 'cut-own-1');

        $response = $this->actingAs($employee)->get(route('pos.cuts.mine', ['date' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('Mi corte');
        $response->assertSee('$100.00');
    }

    public function test_product_with_cost_is_reflected_in_cut_profit(): void
    {
        $admin = $this->employeeWithCut();

        $product = Product::factory()->create([
            'name' => 'Producto con costo',
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 100,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ]);

        $sale = $this->makePaidSale($admin, 100.00, 'cut-profit-1');

        SaleItem::where('sale_id', $sale->id)->delete();

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'category_name' => $product->category?->name,
            'quantity' => 1,
            'unit_price' => 100.00,
            'cost_price' => 60.00,
            'tax_rate' => 0.16,
            'line_subtotal' => 86.21,
            'tax_amount' => 13.79,
            'line_total' => 100.00,
            'line_cost' => 60.00,
            'price_overridden' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('$100.00'); // Ventas
        $response->assertSee('$26.21'); // Ganancia = total 100 - IVA 13.79 - costo 60
        $this->assertSame(1, $response->viewData('totals')['departments'][0]['quantity']);
    }

    public function test_cut_preserves_bulk_quantities_and_kit_revenue_with_fractional_refunds(): void
    {
        $this->freezeTime();
        $employee = $this->employeeWithCut();
        $bulk = Product::factory()->bulk()->create();
        $kit = Product::factory()->kit()->create();
        $sale = $this->makePaidSale($employee, 125.00, 'cut-fractional');
        $bulkItem = $sale->items()->create([
            'product_id' => $bulk->id,
            'product_name' => 'Aceite a granel',
            'sale_mode' => Product::MODE_BULK,
            'category_name' => 'Mostrador',
            'quantity' => 0.25,
            'unit_price' => 100,
            'line_total' => 25,
            'line_cost' => 10,
        ]);
        $sale->items()->create([
            'product_id' => $kit->id,
            'product_name' => 'Paquete comercial',
            'sale_mode' => Product::MODE_KIT,
            'category_name' => 'Mostrador',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'line_cost' => 60,
        ]);
        $return = $sale->returns()->create([
            'requested_by' => $employee->id,
            'approved_by' => $employee->id,
            'status' => SaleReturn::STATUS_APPROVED,
            'reason' => 'Devolución parcial de granel',
            'total' => 12.50,
            'approved_at' => now(),
        ]);
        $return->items()->create([
            'sale_item_id' => $bulkItem->id,
            'quantity' => 0.125,
            'unit_price' => 100,
            'line_total' => 12.50,
        ]);

        $response = $this->actingAs($employee)->get(route('pos.cuts.index'));

        $response->assertOk()->assertSee('Ventas segunda tienda');
        $totals = $response->viewData('totals');
        $this->assertSame([['name' => 'Mostrador', 'quantity' => 1.25, 'total' => 125.0, 'tickets' => 1]], $totals['departments']);
        $this->assertSame(112.5, $totals['totals']['net_sales']);
        $this->assertSame(47.5, $totals['totals']['profit']);
        $this->get(route('pos.cuts.print'))->assertOk()->assertSee('1.25')->assertDontSee('1.250');
    }

    public function test_navidad_panel_shows_only_navidad_products_and_their_total(): void
    {
        $this->freezeTime();
        $employee = $this->employeeWithCut();
        $sale = $this->makePaidSale($employee, 100.00, 'cut-navidad-1');

        SaleItem::where('sale_id', $sale->id)->delete();
        $sale->items()->create([
            'product_name' => 'Arbolito',
            'category_name' => 'Navidad',
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'line_cost' => 40,
        ]);
        $sale->items()->create([
            'product_name' => 'Velas',
            'category_name' => 'navidad',
            'quantity' => 1,
            'unit_price' => 25,
            'line_total' => 25,
            'line_cost' => 10,
        ]);
        $sale->items()->create([
            'product_name' => 'Aceite',
            'category_name' => 'Mostrador',
            'quantity' => 1,
            'unit_price' => 200,
            'line_total' => 200,
            'line_cost' => 100,
        ]);

        $response = $this->actingAs($employee)->get(route('pos.cuts.index'));

        $response->assertOk()->assertSee('Ventas segunda tienda')->assertSee('Arbolito')->assertSee('Velas')
            ->assertSee('$125.00')->assertDontSee('Aceite');
        $this->assertSame([
            ['name' => 'Arbolito', 'quantity' => 2, 'total' => 100.0],
            ['name' => 'Velas', 'quantity' => 1, 'total' => 25.0],
        ], $response->viewData('totals')['navidad_products']);
        $this->assertSame(125.0, $response->viewData('totals')['navidad_total']);
    }

    public function test_printed_legacy_cut_keeps_saved_integer_quantities_instead_of_rebuilding_them(): void
    {
        $this->freezeTime();
        $employee = $this->employeeWithCut();
        DailyCut::create([
            'cut_key' => DailyCut::keyFor(now()->toDateString(), DailyCut::SCOPE_EMPLOYEE, $employee->id),
            'cut_date' => now()->toDateString(),
            'scope' => DailyCut::SCOPE_EMPLOYEE,
            'user_id' => $employee->id,
            'version' => 1,
            'totals' => ['departments' => [['name' => 'Histórico', 'quantity' => 7, 'total' => 700, 'tickets' => 2]]],
            'generated_by' => $employee->id,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($employee)->get(route('pos.cuts.print'));

        $response->assertOk()->assertSee('Histórico')->assertSee('<td class="num">7</td>', false)->assertSee('$700.00');
        $this->assertSame(7, $response->viewData('totals')['departments'][0]['quantity']);
    }

    public function test_own_cut_shows_finalize_day_button(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);

        $response = $this->actingAs($employee)->get(route('pos.cuts.mine'));

        $response->assertOk();
        $response->assertSee(route('pos.cuts.close-my-day'));
        $response->assertDontSee(route('pos.cuts.reopen-my-day'));
    }

    public function test_closed_own_cut_shows_reopen_sale_button(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);

        $this->actingAs($employee)
            ->post(route('pos.cuts.close-my-day'), ['date' => now()->toDateString()])
            ->assertRedirect();

        $response = $this->actingAs($employee)->get(route('pos.cuts.mine'));

        $response->assertOk();
        $response->assertSee(route('pos.cuts.reopen-my-day'));
        $response->assertDontSee(route('pos.cuts.close-my-day'));
    }

    public function test_reopen_sale_allows_sale_and_final_cut_keeps_updated_totals(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);
        $product = $this->saleProduct();

        $this->actingAs($employee)
            ->post(route('pos.cuts.close-my-day'), ['date' => now()->toDateString()])
            ->assertRedirect();

        $blocked = $this->actingAs($employee)
            ->postJson(route('pos.sales.store'), $this->salePayload($product, 'turno-blocked-1'));

        $blocked->assertUnprocessable();
        $blocked->assertJsonPath('day_closed_for_user', true);

        $this->actingAs($employee)
            ->post(route('pos.cuts.reopen-my-day'))
            ->assertRedirect();

        $saleResponse = $this->actingAs($employee)
            ->postJson(route('pos.sales.store'), $this->salePayload($product, 'turno-after-reopen-1'));

        $saleResponse->assertOk();

        $this->actingAs($employee)
            ->post(route('pos.cuts.close-my-day'), ['date' => now()->toDateString()])
            ->assertRedirect();

        $latest = DailyCut::latestVersion(now()->toDateString(), $employee);

        $this->assertNotNull($latest);
        $this->assertTrue($latest->isClosed());
        $this->assertSame(2, $latest->version);
        $this->assertEquals(100.00, (float) ($latest->totals['sales']['total'] ?? 0));
    }

    public function test_reopen_sale_button_is_not_shown_to_another_employee(): void
    {
        $employeeA = User::factory()->create()->assignRole('empleado');
        $employeeA->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);

        $employeeB = User::factory()->create()->assignRole('empleado');
        $employeeB->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);

        $this->actingAs($employeeA)
            ->post(route('pos.cuts.close-my-day'), ['date' => now()->toDateString()])
            ->assertRedirect();

        $response = $this->actingAs($employeeB)->get(route('pos.cuts.mine'));

        $response->assertOk();
        $response->assertDontSee(route('pos.cuts.reopen-my-day'));
    }

    public function test_reopen_sale_button_hides_when_global_day_is_closed(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);

        $this->actingAs($employee)
            ->post(route('pos.cuts.close-my-day'), ['date' => now()->toDateString()])
            ->assertRedirect();

        DailyCut::create([
            'cut_key' => DailyCut::keyFor(now()->toDateString(), DailyCut::SCOPE_GLOBAL),
            'cut_date' => now()->toDateString(),
            'scope' => DailyCut::SCOPE_GLOBAL,
            'user_id' => null,
            'version' => 1,
            'totals' => [],
            'generated_by' => $employee->id,
            'generated_at' => now(),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($employee)->get(route('pos.cuts.mine'));

        $response->assertOk();
        $response->assertDontSee(route('pos.cuts.reopen-my-day'));
    }

    public function test_employee_filter_is_only_available_to_admins(): void
    {
        $this->freezeTime();
        $employee = $this->employeeWithCut(['pos.sales.day.any']);
        $other = User::factory()->create()->assignRole('empleado');
        $other->givePermissionTo(['pos.access', 'pos.sell']);

        $response = $this->actingAs($employee)->get(route('pos.cuts.index', ['employee' => $other->id]));

        $response->assertOk()->assertDontSee('cut-employee')->assertSee('Corte de '.$employee->name);
        $this->assertSame($employee->id, $response->viewData('employee')?->id);

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pos.cuts.index'))
            ->assertOk()->assertSee('cut-employee');
    }

    public function test_admin_does_not_see_reopen_sale_on_employee_cut(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell', 'pos.cut.own']);

        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($employee)
            ->post(route('pos.cuts.close-my-day'), ['date' => now()->toDateString()])
            ->assertRedirect();

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', [
            'date' => now()->toDateString(),
            'employee' => $employee->id,
        ]));

        $response->assertOk();
        $response->assertDontSee(route('pos.cuts.reopen-my-day'));
        $response->assertDontSee(route('pos.cuts.close-my-day'));
    }
}
