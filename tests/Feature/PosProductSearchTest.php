<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pos_search_matches_both_barcodes_but_not_internal_sku(): void
    {
        $product = Product::factory()->create([
            'sku' => 'INTERNAL-ONLY-01',
            'barcode' => '0001234567890',
            'barcode2' => 'A(02)+#',
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['0001234567890', 'A(02)+#'] as $code) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $code]))
                ->assertOk()
                ->assertJsonPath('products.0.id', $product->id)
                ->assertJsonPath('products.0.sale_mode', Product::MODE_UNIT);
        }

        $this->actingAs($employee)
            ->getJson(route('pos.products.search', ['q' => 'INTERNAL-ONLY-01']))
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_imported_or_repaired_code_is_visible_and_scannable_with_manual_second_code(bool $repairExisting): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        if ($repairExisting) {
            Product::factory()->create([
                'sku' => '00012345',
                'barcode' => null,
                'barcode2' => null,
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin)->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent(
                'productos.csv',
                "codigo,nombre,precio_venta\n00012345,Pieza importada,10",
            ),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 1);
        $product = Product::query()->where('sku', '00012345')->firstOrFail();
        $this->assertSame('00012345', $product->barcode);
        $this->assertNull($product->barcode2);
        $response = $this->get(route('admin.products.edit', $product))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $input = (new DOMXPath($document))->query('//input[@name="barcode"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $input);
        $this->assertSame('00012345', $input->getAttribute('value'));

        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'price' => $product->price,
            'tax_rate' => 16,
            'stock' => $product->stock,
            'barcode' => '00012345',
            'barcode2' => 'ALTERNATE(02)+#',
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['00012345', 'ALTERNATE(02)+#'] as $code) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $code]))
                ->assertOk()
                ->assertJsonCount(1, 'products')
                ->assertJsonPath('products.0.id', $product->id)
                ->assertJsonPath('products.0.barcode', '00012345')
                ->assertJsonPath('products.0.barcode2', 'ALTERNATE(02)+#');
        }
    }

    public function test_pos_search_still_finds_products_by_name_and_brand(): void
    {
        $product = Product::factory()->create([
            'name' => 'Balata especial',
            'brand' => 'Marca única',
            'sku' => 'INTERNAL-SEARCH-02',
            'barcode' => null,
            'barcode2' => null,
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['Balata especial', 'Marca única'] as $term) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $term]))
                ->assertOk()
                ->assertJsonPath('products.0.id', $product->id);
        }
    }

    public function test_pos_search_exposes_bulk_precision_and_mode_without_costs_or_recipe(): void
    {
        $product = Product::factory()->bulk()->create([
            'stock' => 2.750,
            'min_stock' => 0.500,
            'price' => 14.10,
            'cost' => 7.50,
            'track_inventory' => true,
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $this->actingAs($employee)
            ->getJson(route('pos.products.search', ['q' => $product->barcode]))
            ->assertOk()
            ->assertJsonPath('products.0.sale_mode', Product::MODE_BULK)
            ->assertJsonPath('products.0.kit_deduct_components', false)
            ->assertJsonPath('products.0.stock', 2.75)
            ->assertJsonPath('products.0.available_stock', 2.75)
            ->assertJsonPath('products.0.min_stock', 0.5)
            ->assertJsonPath('products.0.track_inventory', true)
            ->assertJsonMissingPath('products.0.cost')
            ->assertJsonMissingPath('products.0.kit_items');
    }

    #[TestWith([null, null, null, false])]
    #[TestWith(['0.000', '0.000', null, false])]
    #[TestWith(['12.375', '3.125', 'products/aceite.jpg', true])]
    public function test_search_and_featured_fallback_preserve_optional_metadata(?string $warehouseStock, ?string $store2Stock, ?string $imagePath, bool $hasCategory): void
    {
        $product = Product::factory()->bulk()->create([
            'warehouse_stock' => $warehouseStock,
            'store_2_stock' => $store2Stock,
            'image_path' => $imagePath,
            'featured' => true,
        ]);
        $categoryName = $hasCategory ? $product->category->name : null;
        if (! $hasCategory) {
            $product->update(['category_id' => null]);
        }
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');
        $this->actingAs($employee);

        foreach ([route('pos.products.search', ['q' => $product->barcode]), route('pos.products.frequent')] as $url) {
            $this->getJson($url)->assertOk()
                ->assertJsonPath('products.0.id', $product->id)
                ->assertJsonPath('products.0.category_name', $categoryName)
                ->assertJsonPath('products.0.image_url', $imagePath === null ? null : asset('storage/products/aceite.jpg'))
                ->assertJsonPath('products.0.warehouse_stock', $warehouseStock)
                ->assertJsonPath('products.0.store_2_stock', $store2Stock);
        }
    }

    public function test_frequent_sold_products_include_current_catalog_metadata(): void
    {
        $this->freezeTime();
        $product = Product::factory()->create([
            'featured' => false,
            'image_path' => 'products/balata.jpg',
            'warehouse_stock' => 0,
            'store_2_stock' => null,
            'stock' => 5,
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell']);
        $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'frequent-catalog-metadata',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertOk();

        $this->getJson(route('pos.products.frequent'))->assertOk()
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.category_name', $product->category->name)
            ->assertJsonPath('products.0.image_url', asset('storage/products/balata.jpg'))
            ->assertJsonPath('products.0.warehouse_stock', '0.000')
            ->assertJsonPath('products.0.store_2_stock', null);
    }

    #[TestWith([true, true, 3])]
    #[TestWith([true, false, null])]
    #[TestWith([false, true, null])]
    public function test_search_and_frequent_kit_payload_uses_component_availability_not_parent_stock(bool $deduct, bool $controlled, ?int $available): void
    {
        $kit = Product::factory()->kit()->create([
            'kit_deduct_components' => $deduct,
            'featured' => true,
        ]);
        $component = $kit->kitItems()->sole();
        $component->update(['stock' => 6, 'track_inventory' => $controlled, 'featured' => false]);
        $kit->kitItems()->updateExistingPivot($component->id, ['quantity' => 2]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');
        $this->actingAs($employee);

        foreach ([route('pos.products.search', ['q' => $kit->barcode]), route('pos.products.frequent')] as $url) {
            $this->getJson($url)
                ->assertOk()
                ->assertJsonPath('products.0.id', $kit->id)
                ->assertJsonPath('products.0.sale_mode', Product::MODE_KIT)
                ->assertJsonPath('products.0.kit_deduct_components', $deduct)
                ->assertJsonPath('products.0.stock', $available)
                ->assertJsonPath('products.0.available_stock', $available)
                ->assertJsonPath('products.0.track_inventory', $available !== null)
                ->assertJsonPath('products.0.in_stock', true)
                ->assertJsonMissingPath('products.0.cost');
        }
    }

    public function test_pending_payload_keeps_saved_modes_fractional_quantity_and_kit_flag_without_costs_or_recipe(): void
    {
        $bulk = Product::factory()->bulk()->create(['stock' => 2.750, 'price' => 14.10, 'cost' => 7.50]);
        $kit = Product::factory()->kit()->create(['price' => 30, 'kit_deduct_components' => true]);
        $kit->kitItems()->sole()->update(['stock' => 6]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell']);
        $this->actingAs($employee);
        $saleId = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-modes-payload',
            'status' => 'pending',
            'items' => [
                ['product_id' => $bulk->id, 'quantity' => 0.125],
                ['product_id' => $kit->id, 'quantity' => 1],
            ],
        ])->assertOk()->json('sale.id');
        $bulk->update(['sale_mode' => Product::MODE_UNIT]);
        $kit->update(['kit_deduct_components' => false]);

        $this->getJson(route('pos.sales.show', $saleId))
            ->assertOk()
            ->assertJsonPath('sale.items.0.sale_mode', Product::MODE_BULK)
            ->assertJsonPath('sale.items.0.quantity', 0.125)
            ->assertJsonPath('sale.items.0.track_inventory', false)
            ->assertJsonPath('sale.items.1.sale_mode', Product::MODE_KIT)
            ->assertJsonPath('sale.items.1.kit_deduct_components', true)
            ->assertJsonMissingPath('sale.items.0.cost_price')
            ->assertJsonMissingPath('sale.items.0.line_cost')
            ->assertJsonMissingPath('sale.items.1.inventory_snapshot');
    }
}
