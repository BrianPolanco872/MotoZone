<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function employee(array $permissions = []): User
    {
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo(array_merge(['pos.access', 'pos.sell'], $permissions));

        return $user;
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'name' => 'Kit de prueba',
            'sku' => 'KIT-001',
            'barcode' => '7501234567890',
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 10,
            'min_stock' => 2,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ], $overrides));
    }

    /**
     * @param  array<int, array{quantity: int|float}>  $components
     */
    private function createKit(array $components, bool $deductComponents = true): Product
    {
        $kit = Product::factory()->create([
            'sale_mode' => Product::MODE_KIT,
            'kit_deduct_components' => $deductComponents,
            'price' => 100,
            'stock' => 0,
            'min_stock' => 0,
            'track_inventory' => true,
        ]);
        $kit->kitItems()->sync($components);

        return $kit;
    }

    #[TestWith([0.5, '7.05', 2.25])]
    #[TestWith([0.001, '0.01', 2.749])]
    public function test_bulk_sale_preserves_fractional_quantity_price_stock_and_movement(float $quantity, string $total, float $remainingStock): void
    {
        $product = Product::factory()->bulk()->create(['price' => 14.10, 'stock' => 2.750, 'min_stock' => 0.500]);

        $response = $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'bulk-precision',
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ])->assertOk();

        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $item = $sale->items()->sole();
        $this->assertSame($total, $sale->total);
        $this->assertSame($total, $item->line_total);
        $this->assertSame($quantity, $item->quantity);
        $this->assertSame(Product::MODE_BULK, $item->sale_mode);
        $this->assertSame($remainingStock, $product->fresh()->stock);
        $this->assertSame(0.5, $product->fresh()->min_stock);
        $this->assertSame([
            ['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => $quantity],
        ], $item->inventory_snapshot['effects']);
        $movement = StockMovement::query()->where('sale_id', $sale->id)->sole();
        $this->assertSame(-$quantity, $movement->quantity);
        $this->assertSame(2.75, $movement->stock_before);
        $this->assertSame($remainingStock, $movement->stock_after);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => $total]);
    }

    #[TestWith([0])]
    #[TestWith([0.0001])]
    #[TestWith([-0.001])]
    #[TestWith([9999.001])]
    public function test_invalid_bulk_quantity_returns_422_without_any_sale_effects(int|float $quantity): void
    {
        $product = Product::factory()->bulk()->create(['stock' => 10000]);

        $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'invalid-bulk',
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');

        $this->assertSame(10000, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    #[TestWith(['unit'])]
    #[TestWith(['kit'])]
    #[TestWith(['custom'])]
    public function test_fractional_non_bulk_quantity_returns_422_without_truncating(string $mode): void
    {
        $component = Product::factory()->create(['stock' => 10]);
        $product = $this->createKit([$component->id => ['quantity' => 1]]);
        if ($mode === Product::MODE_UNIT) {
            $product = $component;
        }
        $item = $mode === 'custom'
            ? ['is_custom' => true, 'custom_name' => 'Instalación', 'custom_price' => 10]
            : ['product_id' => $product->id];

        $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'fractional-non-bulk',
            'items' => [[...$item, 'quantity' => 0.5, 'sale_mode' => Product::MODE_BULK]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');

        $this->assertSame(10, $component->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_kit_uses_server_recipe_and_toggle_never_its_own_stock(bool $deductComponents): void
    {
        $sparkPlug = Product::factory()->create(['stock' => $deductComponents ? 10 : 0]);
        $filter = Product::factory()->create(['stock' => $deductComponents ? 10 : 0]);
        $kit = $this->createKit([$sparkPlug->id => ['quantity' => 2], $filter->id => ['quantity' => 1]], $deductComponents);

        $response = $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'kit-server-authority',
            'items' => [[
                'product_id' => $kit->id, 'quantity' => 3,
                'sale_mode' => Product::MODE_UNIT,
                'kit_deduct_components' => ! $deductComponents,
                'components' => [['product_id' => $sparkPlug->id, 'quantity' => 999]],
                'inventory_snapshot' => ['version' => 1, 'mode' => 'unit', 'effects' => []],
            ]],
        ])->assertOk();

        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $item = $sale->items()->sole();
        $this->assertSame('300.00', $sale->total);
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame(Product::MODE_KIT, $item->sale_mode);
        $this->assertSame([
            'version' => 1,
            'mode' => Product::MODE_KIT,
            'kit_deduct_components' => $deductComponents,
            'components' => [
                ['product_id' => $sparkPlug->id, 'product_name' => $sparkPlug->name, 'quantity' => 2, 'sale_mode' => Product::MODE_UNIT],
                ['product_id' => $filter->id, 'product_name' => $filter->name, 'quantity' => 1, 'sale_mode' => Product::MODE_UNIT],
            ],
            'effects' => $deductComponents ? [
                ['product_id' => $sparkPlug->id, 'product_name' => $sparkPlug->name, 'quantity' => 6],
                ['product_id' => $filter->id, 'product_name' => $filter->name, 'quantity' => 3],
            ] : [],
        ], $item->inventory_snapshot);
        $this->assertSame($deductComponents ? 4 : 0, $sparkPlug->fresh()->stock);
        $this->assertSame($deductComponents ? 7 : 0, $filter->fresh()->stock);
        $this->assertSame(0, $kit->fresh()->stock);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $kit->id]);
        $this->assertDatabaseCount('stock_movements', $deductComponents ? 2 : 0);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 300]);
        if ($deductComponents) {
            $this->assertDatabaseHas('stock_movements', ['product_id' => $sparkPlug->id, 'quantity' => -6]);
            $this->assertDatabaseHas('stock_movements', ['product_id' => $filter->id, 'quantity' => -3]);
        }
    }

    public function test_kit_multiplies_bulk_components_exactly_and_skips_untracked_components(): void
    {
        $oil = Product::factory()->bulk()->create(['stock' => 1]);
        $service = Product::factory()->create(['stock' => 0, 'track_inventory' => false]);
        $kit = $this->createKit([$oil->id => ['quantity' => 0.125], $service->id => ['quantity' => 2]]);

        $response = $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'kit-bulk-component',
            'items' => [['product_id' => $kit->id, 'quantity' => 3]],
        ])->assertOk();

        $item = Sale::query()->findOrFail($response->json('sale.id'))->items()->sole();
        $this->assertSame(0.625, $oil->fresh()->stock);
        $this->assertSame(0, $service->fresh()->stock);
        $this->assertSame([
            ['product_id' => $oil->id, 'product_name' => $oil->name, 'quantity' => 0.375],
        ], $item->inventory_snapshot['effects']);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $oil->id, 'quantity' => -0.375]);
    }

    #[TestWith(['paid'])]
    #[TestWith(['pending'])]
    public function test_shared_component_demand_from_repeated_direct_lines_and_two_kits_rolls_back_atomically(string $status): void
    {
        $component = Product::factory()->create(['stock' => 10]);
        $firstKit = $this->createKit([$component->id => ['quantity' => 2]]);
        $secondKit = $this->createKit([$component->id => ['quantity' => 3]]);
        $this->actingAs($this->employee());
        $payload = [
            'idempotency_key' => 'combined-kit-stock', 'status' => $status,
            'items' => [
                ['product_id' => $component->id, 'quantity' => 2],
                ['product_id' => $component->id, 'quantity' => 2],
                ['product_id' => $firstKit->id, 'quantity' => 2],
                ['product_id' => $secondKit->id, 'quantity' => 1],
            ],
        ];

        if ($status === 'pending') {
            $response = $this->postJson(route('pos.sales.store'), $payload)->assertOk();
            $sale = Sale::query()->findOrFail($response->json('sale.id'));
            $itemsBefore = $sale->items->toArray();
            $this->postJson(route('pos.sales.charge', $sale), [])->assertUnprocessable()->assertJsonValidationErrors('items');
            $this->assertSame('pending', $sale->fresh()->status);
            $this->assertSame('0.00', $sale->fresh()->amount_paid);
            $this->assertSame($itemsBefore, $sale->fresh()->items->toArray());
        } else {
            $this->postJson(route('pos.sales.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
            $this->assertDatabaseCount('sales', 0);
            $this->assertDatabaseCount('sale_items', 0);
        }

        $this->assertSame(10, $component->fresh()->stock);
        $this->assertSame(0, $firstKit->fresh()->stock);
        $this->assertSame(0, $secondKit->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_repeated_kit_lines_preserve_authorized_prices_and_per_line_effects(): void
    {
        $component = Product::factory()->create(['stock' => 10]);
        $kit = $this->createKit([$component->id => ['quantity' => 2]]);

        $response = $this->actingAs($this->employee(['pos.price.edit']))->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'kit-line-prices',
            'items' => [
                ['product_id' => $kit->id, 'quantity' => 2, 'unit_price' => 100],
                ['product_id' => $kit->id, 'quantity' => 3, 'unit_price' => 90],
            ],
        ])->assertOk();

        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $items = $sale->items()->orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertSame(['100.00', '90.00'], $items->pluck('unit_price')->all());
        $this->assertSame([4, 6], $items->map(fn (SaleItem $item): int => $item->inventory_snapshot['effects'][0]['quantity'])->all());
        $this->assertSame('470.00', $sale->total);
        $this->assertSame(0, $component->fresh()->stock);
    }

    public function test_kit_idempotency_applies_component_effects_and_cash_only_once(): void
    {
        $component = Product::factory()->create(['stock' => 10]);
        $kit = $this->createKit([$component->id => ['quantity' => 2]]);
        $this->actingAs($this->employee());
        $payload = ['idempotency_key' => 'kit-idempotent', 'items' => [['product_id' => $kit->id, 'quantity' => 3]]];

        $first = $this->postJson(route('pos.sales.store'), $payload)->assertOk();
        $this->postJson(route('pos.sales.store'), $payload)->assertOk()->assertJsonPath('sale.id', $first->json('sale.id'));

        $this->assertSame(4, $component->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('cash_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $component->id, 'quantity' => -6]);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_pending_bulk_and_kit_freeze_recipe_toggle_and_amounts_until_charged_once(bool $deductComponents): void
    {
        $component = Product::factory()->create(['stock' => 10]);
        $replacement = Product::factory()->create(['stock' => 10]);
        $bulk = Product::factory()->bulk()->create(['stock' => 2.75, 'price' => 14.10]);
        $kit = $this->createKit([$component->id => ['quantity' => 2]], $deductComponents);
        $this->actingAs($this->employee());
        $response = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-frozen-recipe', 'status' => 'pending',
            'items' => [['product_id' => $kit->id, 'quantity' => 3], ['product_id' => $bulk->id, 'quantity' => 0.5]],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $kitItem = $sale->items()->where('product_id', $kit->id)->sole();
        $snapshot = $kitItem->inventory_snapshot;
        $this->assertNull($snapshot['effects']);
        $this->assertSame($deductComponents, $snapshot['kit_deduct_components']);
        $this->assertSame(2, $snapshot['components'][0]['quantity']);
        $this->assertSame(10, $component->fresh()->stock);
        $this->assertSame(2.75, $bulk->fresh()->stock);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->getJson(route('pos.sales.show', $sale))->assertOk()->assertJsonPath('sale.items.1.quantity', 0.5);
        $kit->kitItems()->sync([$replacement->id => ['quantity' => 9]]);
        $kit->update(['kit_deduct_components' => ! $deductComponents, 'price' => 999]);
        $bulk->update(['price' => 999]);

        $this->postJson(route('pos.sales.charge', $sale), ['amount_paid' => 307.05])->assertOk();
        $this->postJson(route('pos.sales.charge', $sale), ['amount_paid' => 307.05])
            ->assertUnprocessable()->assertJsonValidationErrors('sale');

        $this->assertSame('307.05', $sale->fresh()->total);
        $this->assertSame('307.05', $sale->fresh()->amount_paid);
        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame('100.00', $kitItem->fresh()->unit_price);
        $this->assertSame([...$snapshot, 'effects' => $deductComponents ? [
            ['product_id' => $component->id, 'product_name' => $component->name, 'quantity' => 6],
        ] : []], $kitItem->fresh()->inventory_snapshot);
        $this->assertSame($deductComponents ? 4 : 10, $component->fresh()->stock);
        $this->assertSame(10, $replacement->fresh()->stock);
        $this->assertSame(2.25, $bulk->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', $deductComponents ? 2 : 1);
        $this->assertDatabaseCount('cash_movements', 1);
    }

    public function test_pending_kit_with_deleted_frozen_component_returns_422_without_charging(): void
    {
        $component = Product::factory()->create(['stock' => 10]);
        $replacement = Product::factory()->create(['stock' => 10]);
        $kit = $this->createKit([$component->id => ['quantity' => 2]]);
        $this->actingAs($this->employee());
        $response = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-deleted-component', 'status' => 'pending',
            'items' => [['product_id' => $kit->id, 'quantity' => 3]],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $snapshot = $sale->items()->sole()->inventory_snapshot;
        $kit->kitItems()->sync([$replacement->id => ['quantity' => 1]]);
        $component->delete();

        $this->postJson(route('pos.sales.charge', $sale), [])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertSame('pending', $sale->fresh()->status);
        $this->assertSame('0.00', $sale->fresh()->amount_paid);
        $this->assertNull($sale->fresh()->paid_at);
        $this->assertSame($snapshot, $sale->items()->sole()->inventory_snapshot);
        $this->assertSame(10, $replacement->fresh()->stock);
        $this->assertModelMissing($component);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_legacy_pending_unit_line_without_inventory_snapshot_can_be_charged(): void
    {
        $product = $this->createProduct(['stock' => 5]);
        $employee = $this->employee();
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'legacy-pending-unit', 'status' => 'pending',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);
        $sale->items()->update(['sale_mode' => Product::MODE_UNIT, 'inventory_snapshot' => null]);

        $this->actingAs($employee)->postJson(route('pos.sales.charge', $sale), ['amount_paid' => 200])->assertOk();

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame('200.00', $sale->fresh()->total);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => -2]);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 200]);
    }

    public function test_fractional_product_id_returns_422_instead_of_selling_a_truncated_id(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'fractional-product-id',
            'items' => [['product_id' => $product->id.'.9', 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    #[TestWith(['parent'])]
    #[TestWith(['component'])]
    public function test_pending_kit_with_inactive_parent_or_component_returns_422_without_charging(string $inactiveTarget): void
    {
        $component = Product::factory()->create(['stock' => 10]);
        $kit = $this->createKit([$component->id => ['quantity' => 2]]);
        $this->actingAs($this->employee());
        $response = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-inactive-product', 'status' => 'pending',
            'items' => [['product_id' => $kit->id, 'quantity' => 3]],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $snapshot = $sale->items()->sole()->inventory_snapshot;
        $inactiveProduct = $inactiveTarget === 'parent' ? $kit : $component;
        $inactiveProduct->update(['is_active' => false]);

        $this->postJson(route('pos.sales.charge', $sale), [])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertSame('pending', $sale->fresh()->status);
        $this->assertSame('0.00', $sale->fresh()->amount_paid);
        $this->assertNull($sale->fresh()->paid_at);
        $this->assertSame($snapshot, $sale->items()->sole()->inventory_snapshot);
        $this->assertSame(10, $component->fresh()->stock);
        $this->assertSame(0, $kit->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_bulk_sale_still_requires_sell_and_manual_price_permissions(): void
    {
        $product = Product::factory()->bulk()->create(['stock' => 2.75, 'price' => 14.10]);
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo('pos.access');
        $payload = ['idempotency_key' => 'bulk-permissions', 'items' => [['product_id' => $product->id, 'quantity' => 0.5, 'unit_price' => 0.01]]];

        $this->actingAs($user)->postJson(route('pos.sales.store'), $payload)->assertForbidden();

        $this->assertSame(2.75, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->actingAs($this->employee())->postJson(route('pos.sales.store'), $payload)->assertOk();
        $this->assertDatabaseHas('sale_items', ['product_id' => $product->id, 'unit_price' => 14.10, 'line_total' => 7.05]);
    }

    public function test_employee_can_create_paid_sale_and_deducts_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'test-sale-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertOk();

        $sale = Sale::where('idempotency_key', 'test-sale-001')->first();
        $this->assertNotNull($sale);
        $this->assertNotNull($sale->folio);
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals(200.00, (float) $sale->total);
        $this->assertEquals(200.00, (float) $sale->amount_paid);

        $product->refresh();
        $this->assertEquals(3, $product->stock);
    }

    public function test_idempotency_prevents_duplicate_sale(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'idem-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'idem-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertCount(1, Sale::where('idempotency_key', 'idem-001')->get());
        $product->refresh();
        $this->assertEquals(3, $product->stock); // Solo se descontó una vez
    }

    #[TestWith([null, '0.000'])]
    #[TestWith(['8.000', '4.000'])]
    public function test_paid_sale_deducts_only_store_stock_and_preserves_optional_locations(?string $warehouseStock, string $store2Stock): void
    {
        $product = $this->createProduct([
            'stock' => 5,
            'warehouse_stock' => $warehouseStock,
            'store_2_stock' => $store2Stock,
        ]);

        $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'store-only-stock',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk();

        $product->refresh();
        $this->assertSame(3, $product->stock);
        $this->assertSame($warehouseStock, $product->warehouse_stock);
        $this->assertSame($store2Stock, $product->store_2_stock);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => -2]);
    }

    #[TestWith([null])]
    #[TestWith(['products/actual.jpg'])]
    public function test_pending_metadata_uses_catalog_without_replacing_saved_sale_values(?string $imagePath): void
    {
        $product = $this->createProduct(['stock' => 5]);
        $this->actingAs($this->employee());
        $saleId = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-current-metadata',
            'status' => 'pending',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk()->json('sale.id');
        $sale = Sale::query()->findOrFail($saleId);
        $itemBefore = $sale->items()->sole()->getAttributes();
        $product->update([
            'name' => 'Nombre actual',
            'sku' => 'ACTUAL',
            'barcode' => '999999',
            'price' => 999,
            'tax_rate' => 0,
            'category_id' => $imagePath === null ? null : $product->category_id,
            'image_path' => $imagePath,
            'warehouse_stock' => 0,
            'store_2_stock' => 7,
        ]);

        $this->getJson(route('pos.sales.show', $sale))->assertOk()
            ->assertJsonPath('sale.total', 200)
            ->assertJsonPath('sale.items.0.name', 'Kit de prueba')
            ->assertJsonPath('sale.items.0.sku', 'KIT-001')
            ->assertJsonPath('sale.items.0.barcode', '7501234567890')
            ->assertJsonPath('sale.items.0.unit_price', 100)
            ->assertJsonPath('sale.items.0.quantity', 2)
            ->assertJsonPath('sale.items.0.tax_rate', 0.16)
            ->assertJsonPath('sale.items.0.category_name', $product->category?->name)
            ->assertJsonPath('sale.items.0.image_url', $imagePath === null ? null : asset('storage/products/actual.jpg'))
            ->assertJsonPath('sale.items.0.warehouse_stock', '0.000')
            ->assertJsonPath('sale.items.0.store_2_stock', '7.000')
            ->assertJsonPath('sale.items.0.stock', null)
            ->assertJsonPath('sale.items.0.track_inventory', false)
            ->assertJsonPath('sale.items.0.wholesale_price', null);

        $this->assertSame($itemBefore, $sale->items()->sole()->getAttributes());
        $this->assertSame('200.00', $sale->fresh()->total);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_wholesale_flag_applies_per_line_and_leaves_other_lines_at_catalog_price(): void
    {
        $wholesaleProduct = $this->createProduct(['sku' => 'MAY-001', 'barcode' => '7500000000001', 'price' => 100, 'wholesale_price' => 80]);
        $regularProduct = $this->createProduct(['sku' => 'REG-001', 'barcode' => '7500000000002', 'price' => 50, 'wholesale_price' => 40]);

        $this->actingAs($this->employee(['pos.wholesale']))
            ->postJson(route('pos.sales.store'), [
                'idempotency_key' => 'wholesale-per-line',
                'status' => 'paid',
                'amount_paid' => 130,
                'items' => [
                    ['product_id' => $wholesaleProduct->id, 'quantity' => 1, 'wholesale' => true],
                    ['product_id' => $regularProduct->id, 'quantity' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('sale.total', 130);

        $this->assertDatabaseHas('sale_items', ['product_id' => $wholesaleProduct->id, 'unit_price' => 80]);
        $this->assertDatabaseHas('sale_items', ['product_id' => $regularProduct->id, 'unit_price' => 50]);
    }

    public function test_sale_cannot_exceed_available_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 1, 'warehouse_stock' => 100, 'store_2_stock' => 100]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'stock-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertUnprocessable();
        $this->assertCount(0, Sale::where('idempotency_key', 'stock-001')->get());
        $product->refresh();
        $this->assertEquals(1, $product->stock);
        $this->assertSame('100.000', $product->warehouse_stock);
        $this->assertSame('100.000', $product->store_2_stock);
    }

    public function test_pending_sale_can_be_charged_and_updates_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $createResponse = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-001',
            'status' => 'pending',
            'amount_paid' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $saleId = $createResponse->json('sale.id');

        $this->assertEquals(5, $product->refresh()->stock); // Stock no descontado aún

        $chargeResponse = $this->actingAs($employee)->postJson(route('pos.sales.charge', $saleId), [
            'amount_paid' => 232.00,
        ]);

        $chargeResponse->assertOk();

        $this->assertEquals(3, $product->refresh()->stock); // Ahora se descuenta

        $sale = Sale::find($saleId);
        $this->assertEquals('paid', $sale->status);
        $this->assertNotNull($sale->paid_at);
    }

    public function test_pending_catalog_sale_remains_loadable_but_charge_returns_422_after_clearing_inventory(): void
    {
        $this->freezeTime();
        $employee = $this->employee(['admin.products.manage']);
        $product = $this->createProduct(['stock' => 5, 'barcode' => '00012345']);
        $this->actingAs($employee);
        $response = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-cleared-inventory',
            'status' => 'pending',
            'amount_paid' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $saleItem = $sale->items()->sole();
        $saleBefore = $sale->getAttributes();
        $itemBefore = $saleItem->getAttributes();
        $this->assertSame('pending', $sale->status);
        $this->assertSame('0.00', $sale->amount_paid);
        $this->assertSame(5, $product->fresh()->stock);

        $this->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se eliminaron 1 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->getJson(route('pos.sales.show', $sale))->assertOk()
            ->assertJsonPath('sale.id', $sale->id)
            ->assertJsonPath('sale.total', 200)
            ->assertJsonPath('sale.items.0.sale_item_id', $saleItem->id)
            ->assertJsonPath('sale.items.0.product_id', null)
            ->assertJsonPath('sale.items.0.is_custom', false)
            ->assertJsonPath('sale.items.0.custom_name', null)
            ->assertJsonPath('sale.items.0.custom_price', null)
            ->assertJsonPath('sale.items.0.name', 'Kit de prueba')
            ->assertJsonPath('sale.items.0.sku', 'KIT-001')
            ->assertJsonPath('sale.items.0.barcode', '00012345')
            ->assertJsonPath('sale.items.0.unit_price', 100)
            ->assertJsonPath('sale.items.0.image_url', null)
            ->assertJsonPath('sale.items.0.category_name', null)
            ->assertJsonPath('sale.items.0.warehouse_stock', null)
            ->assertJsonPath('sale.items.0.store_2_stock', null)
            ->assertJsonPath('sale.items.0.quantity', 2);
        $this->assertSame([...$itemBefore, 'product_id' => null], $saleItem->fresh()->getAttributes());

        $this->postJson(route('pos.sales.charge', $sale), ['amount_paid' => 200])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items')
            ->assertJsonPath('errors.items.0', 'El producto «Kit de prueba» ya no existe.');

        $this->assertSame($saleBefore, $sale->fresh()->getAttributes());
        $this->assertSame([...$itemBefore, 'product_id' => null], $saleItem->fresh()->getAttributes());
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_custom_only_sale_is_paid_without_inventory_movements(): void
    {
        $employee = $this->employee(['pos.tickets.print.own']);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'custom-only',
            'items' => [['is_custom' => true, 'custom_name' => 'Mano de obra', 'custom_price' => 25.50, 'quantity' => 2]],
        ])->assertOk();

        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $this->assertSame('51.00', $sale->total);
        $this->assertSame('paid', $sale->status);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id, 'product_id' => null, 'is_custom' => true,
            'product_name' => 'Mano de obra', 'custom_price' => 25.50, 'quantity' => 2,
        ]);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 51]);
        $this->get(route('pos.sales.ticket', $sale))->assertOk()->assertSee('Mano de obra');
    }

    public function test_pending_sale_with_catalog_and_distinct_custom_items_can_be_loaded_and_charged(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);
        $this->actingAs($employee);
        $response = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'custom-pending', 'status' => 'pending',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['is_custom' => true, 'custom_name' => 'Instalación', 'custom_price' => 30, 'quantity' => 1],
                ['is_custom' => true, 'custom_name' => 'Revisión', 'custom_price' => 20, 'quantity' => 1],
            ],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->getJson(route('pos.sales.show', $sale))->assertOk()
            ->assertJsonPath('sale.items.1.is_custom', true)
            ->assertJsonPath('sale.items.1.custom_name', 'Instalación')
            ->assertJsonPath('sale.items.1.custom_price', 30)
            ->assertJsonPath('sale.items.1.image_url', null)
            ->assertJsonPath('sale.items.1.category_name', null)
            ->assertJsonPath('sale.items.1.warehouse_stock', null)
            ->assertJsonPath('sale.items.1.store_2_stock', null)
            ->assertJsonPath('sale.items.2.custom_name', 'Revisión');

        $this->postJson(route('pos.sales.charge', $sale), ['amount_paid' => 150])->assertOk();

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame('150.00', $sale->fresh()->total);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(['Instalación', 'Revisión'], $sale->items()->where('is_custom', true)->orderBy('id')->pluck('custom_name')->all());
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 150]);
    }

    #[TestWith([['is_custom' => true, 'custom_price' => 10], 'items.0.custom_name'])]
    #[TestWith([['is_custom' => true, 'custom_name' => 'Libre'], 'items.0.custom_price'])]
    #[TestWith([['is_custom' => true, 'custom_name' => 'Libre', 'custom_price' => 0], 'items.0.custom_price'])]
    #[TestWith([['is_custom' => false], 'items.0.product_id'])]
    public function test_custom_and_catalog_items_require_their_own_fields(array $item, string $field): void
    {
        $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'invalid-custom', 'items' => [['quantity' => 1, ...$item]],
        ])->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    #[TestWith(['paid'])]
    #[TestWith(['pending'])]
    public function test_repeated_catalog_lines_cannot_exceed_combined_available_stock(string $status): void
    {
        $product = $this->createProduct(['stock' => 5]);
        $this->actingAs($this->employee());
        $payload = [
            'idempotency_key' => 'repeated-stock', 'status' => $status,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ];

        if ($status === 'pending') {
            $created = $this->postJson(route('pos.sales.store'), $payload)->assertOk();
            $this->postJson(route('pos.sales.charge', $created->json('sale.id')), [])
                ->assertUnprocessable()->assertJsonValidationErrors('items');
            $this->assertDatabaseHas('sales', ['id' => $created->json('sale.id'), 'status' => 'pending']);
        } else {
            $this->postJson(route('pos.sales.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
            $this->assertDatabaseCount('sales', 0);
        }

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_repeated_product_keeps_separate_authorized_prices_when_combined_stock_is_sufficient(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->actingAs($this->employee(['pos.price.edit']))->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'repeated-prices',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 90],
            ],
        ])->assertOk();

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame(['100.00', '90.00'], SaleItem::query()->orderBy('id')->pluck('unit_price')->all());
        $this->assertDatabaseHas('sales', ['total' => 470]);
    }

    public function test_day_filter_uses_selected_employee_and_preserves_employee_isolation(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');
        $firstEmployee = $this->employee();
        $selectedEmployee = $this->employee();
        $product = $this->createProduct();
        $service = app(SaleService::class);
        $firstSale = $service->create($firstEmployee, ['idempotency_key' => 'filter-first', 'items' => [['product_id' => $product->id, 'quantity' => 1]]]);
        $selectedSale = $service->create($selectedEmployee, ['idempotency_key' => 'filter-selected', 'items' => [['product_id' => $product->id, 'quantity' => 1]]]);

        $response = $this->actingAs($admin)->get(route('pos.sales.day', ['employee' => $selectedEmployee->id]))->assertOk();

        $this->assertSame([$selectedSale->id], $response->viewData('sales')->getCollection()->modelKeys());
        $this->assertSame(1, $response->viewData('summary')['count']);
        $ownResponse = $this->actingAs($firstEmployee)->get(route('pos.sales.day', ['employee' => $selectedEmployee->id]))->assertOk();
        $this->assertSame([$firstSale->id], $ownResponse->viewData('sales')->getCollection()->modelKeys());
    }
}
