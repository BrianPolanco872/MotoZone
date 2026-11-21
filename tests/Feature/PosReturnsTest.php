<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DailyCutService;
use App\Services\ReturnService;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosReturnsTest extends TestCase
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
        $user->givePermissionTo(array_merge(['pos.access', 'pos.sell', 'pos.returns.request', 'pos.return.do'], $permissions));

        return $user;
    }

    private function paidSaleWithProduct(User $employee, int $quantity = 2): array
    {
        $product = Product::factory()->create([
            'name' => 'Producto para devolución',
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 10,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ]);

        $sale = Sale::create([
            'user_id' => $employee->id,
            'folio' => 'V-000100',
            'status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 172.41,
            'tax_total' => 27.59,
            'total' => 200.00,
            'amount_paid' => 200.00,
            'change_amount' => 0.00,
            'idempotency_key' => 'sale-return-'.uniqid(),
            'paid_at' => now(),
        ]);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'category_name' => $product->category?->name,
            'quantity' => $quantity,
            'unit_price' => 100.00,
            'cost_price' => 60.00,
            'tax_rate' => 0.16,
            'line_subtotal' => 172.41,
            'tax_amount' => 27.59,
            'line_total' => 200.00,
            'line_cost' => 120.00,
            'price_overridden' => false,
        ]);

        CashMovement::create([
            'user_id' => $employee->id,
            'sale_id' => $sale->id,
            'type' => 'in',
            'category' => 'sale',
            'amount' => 200.00,
            'concept' => 'Venta '.$sale->folio,
            'happened_at' => now(),
        ]);

        return ['sale' => $sale, 'saleItem' => $saleItem, 'product' => $product];
    }

    /**
     * @return array{sale: Sale, saleItem: SaleItem, kit: Product, sparkPlug: Product, filter: Product}
     */
    private function paidKitSale(User $employee, bool $deductComponents = true, bool $trackFilter = true): array
    {
        $sparkPlug = Product::factory()->create(['stock' => 10, 'track_inventory' => true]);
        $filter = Product::factory()->create(['stock' => 10, 'track_inventory' => $trackFilter]);
        $kit = Product::factory()->create([
            'sale_mode' => Product::MODE_KIT,
            'kit_deduct_components' => $deductComponents,
            'price' => 100,
            'stock' => 0,
            'min_stock' => 0,
            'track_inventory' => true,
        ]);
        $kit->kitItems()->attach([$sparkPlug->id => ['quantity' => 2], $filter->id => ['quantity' => 1]]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'paid-kit-return',
            'items' => [['product_id' => $kit->id, 'quantity' => 3]],
        ]);

        return ['sale' => $sale, 'saleItem' => $sale->items->sole(), 'kit' => $kit, 'sparkPlug' => $sparkPlug, 'filter' => $filter];
    }

    private function requestItemReturn(User $employee, Sale $sale, SaleItem $item, int|float $quantity): SaleReturn
    {
        $this->actingAs($employee)->postJson(route('pos.returns.store'), [
            'sale_id' => $sale->id,
            'reason' => 'Devolución parcial',
            'items' => [['sale_item_id' => $item->id, 'quantity' => $quantity]],
        ])->assertRedirect(route('pos.returns.index'))->assertSessionHasNoErrors();

        return SaleReturn::query()->where('sale_id', $sale->id)->orderByDesc('id')->firstOrFail();
    }

    public function test_returning_one_of_three_kits_restores_historical_components_after_recipe_toggle_and_tracking_change(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidKitSale($employee);
        $snapshot = $data['saleItem']->inventory_snapshot;
        $replacement = Product::factory()->create(['stock' => 10]);
        $data['kit']->kitItems()->sync([$replacement->id => ['quantity' => 9]]);
        $data['kit']->update(['kit_deduct_components' => false, 'price' => 999]);
        $data['sparkPlug']->update(['track_inventory' => false]);
        $return = $this->requestItemReturn($employee, $data['sale'], $data['saleItem'], 1);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))
            ->assertRedirect(route('pos.returns.index'))->assertSessionHasNoErrors();

        $this->assertSame(SaleReturn::STATUS_APPROVED, $return->fresh()->status);
        $this->assertSame('100.00', $return->fresh()->total);
        $this->assertSame(1, $data['saleItem']->fresh()->returned_quantity);
        $this->assertSame($snapshot, $data['saleItem']->fresh()->inventory_snapshot);
        $this->assertSame(6, $data['sparkPlug']->fresh()->stock);
        $this->assertSame(8, $data['filter']->fresh()->stock);
        $this->assertSame(10, $replacement->fresh()->stock);
        $this->assertSame(0, $data['kit']->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $data['sparkPlug']->id, 'type' => StockMovement::TYPE_RETURN, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $data['filter']->id, 'type' => StockMovement::TYPE_RETURN, 'quantity' => 1]);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $data['kit']->id]);
        $this->assertDatabaseCount('stock_movements', 4);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $data['sale']->id, 'category' => 'refund', 'amount' => 100]);
    }

    public function test_kit_sold_with_deduction_disabled_never_restores_stock_after_enabling_it(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidKitSale($employee, false);
        $this->assertSame([], $data['saleItem']->inventory_snapshot['effects']);
        $data['kit']->update(['kit_deduct_components' => true]);
        $return = $this->requestItemReturn($employee, $data['sale'], $data['saleItem'], 1);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))
            ->assertRedirect(route('pos.returns.index'))->assertSessionHasNoErrors();

        $this->assertSame(10, $data['sparkPlug']->fresh()->stock);
        $this->assertSame(10, $data['filter']->fresh()->stock);
        $this->assertSame(0, $data['kit']->fresh()->stock);
        $this->assertSame(1, $data['saleItem']->fresh()->returned_quantity);
        $this->assertSame('100.00', $return->fresh()->total);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('cash_movements', ['category' => 'refund', 'amount' => 100]);
    }

    public function test_kit_return_does_not_restock_a_component_that_was_untracked_at_payment(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidKitSale($employee, true, false);
        $this->assertSame([
            ['product_id' => $data['sparkPlug']->id, 'product_name' => $data['sparkPlug']->name, 'quantity' => 6],
        ], $data['saleItem']->inventory_snapshot['effects']);
        $data['filter']->update(['track_inventory' => true]);
        $return = $this->requestItemReturn($employee, $data['sale'], $data['saleItem'], 1);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(6, $data['sparkPlug']->fresh()->stock);
        $this->assertSame(10, $data['filter']->fresh()->stock);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $data['filter']->id]);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('cash_movements', ['category' => 'refund', 'amount' => 100]);
    }

    public function test_bulk_partial_return_preserves_exact_quantities_and_historical_debit(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->bulk()->create(['stock' => 2.75, 'price' => 14.10]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'bulk-partial-return',
            'items' => [['product_id' => $product->id, 'quantity' => 0.5]],
        ]);
        $item = $sale->items->sole();
        $this->assertSame(2.25, $product->fresh()->stock);
        $product->update(['track_inventory' => false]);
        $return = $this->requestItemReturn($employee, $sale, $item, 0.125);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2.375, $product->fresh()->stock);
        $this->assertSame(0.5, $item->fresh()->quantity);
        $this->assertSame(0.125, $item->fresh()->returned_quantity);
        $this->assertSame(0.375, $item->fresh()->availableReturnQuantity());
        $this->assertSame(0.125, $return->items()->sole()->quantity);
        $this->assertSame('1.76', $return->fresh()->total);
        $movement = StockMovement::query()->where('type', StockMovement::TYPE_RETURN)->sole();
        $this->assertSame(0.125, $movement->quantity);
        $this->assertSame(2.25, $movement->stock_before);
        $this->assertSame(2.375, $movement->stock_after);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'category' => 'refund', 'amount' => 1.76]);
    }

    public function test_whole_kit_return_restores_its_fractional_bulk_component_exactly(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $oil = Product::factory()->bulk()->create(['stock' => 1]);
        $kit = Product::factory()->create([
            'sale_mode' => Product::MODE_KIT, 'kit_deduct_components' => true,
            'stock' => 0, 'min_stock' => 0, 'track_inventory' => false, 'price' => 100,
        ]);
        $kit->kitItems()->attach($oil->id, ['quantity' => 0.125]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'kit-bulk-return',
            'items' => [['product_id' => $kit->id, 'quantity' => 3]],
        ]);
        $this->assertSame(0.625, $oil->fresh()->stock);
        $return = $this->requestItemReturn($employee, $sale, $sale->items->sole(), 1);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(0.75, $oil->fresh()->stock);
        $this->assertSame(1, $sale->items()->sole()->returned_quantity);
        $this->assertSame('100.00', $return->fresh()->total);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $oil->id, 'type' => StockMovement::TYPE_RETURN, 'quantity' => 0.125]);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'category' => 'refund', 'amount' => 100]);
    }

    public function test_three_milliunit_returns_reconcile_to_two_cents_and_cannot_refund_more(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->bulk()->create(['stock' => 1, 'price' => 5]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'bulk-cent-reconciliation',
            'items' => [['product_id' => $product->id, 'quantity' => 0.003]],
        ]);
        $item = $sale->items->sole();
        $this->assertSame('0.02', $item->line_total);

        foreach (['0.01', '0.00', '0.01'] as $expectedRefund) {
            $return = $this->requestItemReturn($employee, $sale, $item, 0.001);
            $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($expectedRefund, $return->fresh()->total);
        }

        $this->assertSame(0.003, $item->fresh()->returned_quantity);
        $this->assertSame(0, $item->fresh()->availableReturnQuantity());
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0.02, (float) CashMovement::query()->where('category', 'refund')->sum('amount'));
        $this->assertSame(0.02, (float) SaleReturn::query()->where('status', SaleReturn::STATUS_APPROVED)->sum('total'));
        $this->actingAs($employee)->postJson(route('pos.returns.store'), [
            'sale_id' => $sale->id, 'reason' => 'No debe exceder el saldo',
            'items' => [['sale_item_id' => $item->id, 'quantity' => 0.001]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('sale_returns', 3);
        $this->assertSame(3, CashMovement::query()->where('category', 'refund')->count());
        $this->assertSame(0.003, $item->fresh()->returned_quantity);
    }

    public function test_pending_return_approvals_recalculate_cents_against_the_current_refund_balance(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->bulk()->create(['stock' => 1, 'price' => 5]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'pending-refund-cents',
            'items' => [['product_id' => $product->id, 'quantity' => 0.003]],
        ]);
        $item = $sale->items->sole();
        $first = $this->requestItemReturn($employee, $sale, $item, 0.001);
        $second = $this->requestItemReturn($employee, $sale, $item, 0.001);
        $third = $this->requestItemReturn($employee, $sale, $item, 0.001);
        $this->assertSame(['0.01', '0.01', '0.01'], [$first->total, $second->total, $third->total]);

        foreach ([$first, $second, $third] as $return) {
            $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))->assertRedirect()->assertSessionHasNoErrors();
        }

        $this->assertSame(['0.01', '0.00', '0.01'], SaleReturn::query()->orderBy('id')->pluck('total')->all());
        $this->assertSame(0.02, (float) CashMovement::query()->where('category', 'refund')->sum('amount'));
        $this->assertSame(0.003, $item->fresh()->returned_quantity);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_approval_of_overlapping_pending_bulk_returns_returns_422_without_exceeding_quantity_or_cash(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->bulk()->create(['stock' => 1, 'price' => 5]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'pending-refund-overlap',
            'items' => [['product_id' => $product->id, 'quantity' => 0.003]],
        ]);
        $item = $sale->items->sole();
        $first = $this->requestItemReturn($employee, $sale, $item, 0.002);
        $second = $this->requestItemReturn($employee, $sale, $item, 0.002);
        $this->actingAs($admin)->putJson(route('pos.returns.approve', $first))->assertRedirect()->assertSessionHasNoErrors();

        $this->putJson(route('pos.returns.approve', $second))->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertSame(SaleReturn::STATUS_PENDING, $second->fresh()->status);
        $this->assertSame(0.002, $item->fresh()->returned_quantity);
        $this->assertSame(0.999, $product->fresh()->stock);
        $this->assertSame(0.01, (float) CashMovement::query()->where('category', 'refund')->sum('amount'));
        $this->assertSame(1, CashMovement::query()->where('category', 'refund')->count());
        $this->assertSame(1, StockMovement::query()->where('type', StockMovement::TYPE_RETURN)->count());
    }

    public function test_fractional_kit_return_returns_422_without_any_refund_or_restock(): void
    {
        $employee = $this->employee();
        $data = $this->paidKitSale($employee);

        $this->actingAs($employee)->postJson(route('pos.returns.store'), [
            'sale_id' => $data['sale']->id, 'reason' => 'Medio paquete',
            'items' => [['sale_item_id' => $data['saleItem']->id, 'quantity' => 0.5]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items')
            ->assertJsonPath('errors.items.0', 'Solo los productos a granel permiten devoluciones fraccionarias.');

        $this->assertSame(0, $data['saleItem']->fresh()->returned_quantity);
        $this->assertSame(4, $data['sparkPlug']->fresh()->stock);
        $this->assertSame(7, $data['filter']->fresh()->stock);
        $this->assertDatabaseCount('sale_returns', 0);
        $this->assertSame(0, CashMovement::query()->where('category', 'refund')->count());
        $this->assertSame(0, StockMovement::query()->where('type', StockMovement::TYPE_RETURN)->count());
    }

    public function test_deleted_kit_component_is_not_recreated_but_refund_and_surviving_component_are_preserved(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidKitSale($employee);
        $snapshot = $data['saleItem']->inventory_snapshot;
        $data['kit']->kitItems()->detach($data['sparkPlug']->id);
        $data['sparkPlug']->delete();
        $return = $this->requestItemReturn($employee, $data['sale'], $data['saleItem'], 1);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertModelMissing($data['sparkPlug']);
        $this->assertDatabaseCount('products', 2);
        $this->assertSame(8, $data['filter']->fresh()->stock);
        $this->assertSame($snapshot, $data['saleItem']->fresh()->inventory_snapshot);
        $this->assertSame('100.00', $return->fresh()->total);
        $this->assertSame(1, $data['saleItem']->fresh()->returned_quantity);
        $this->assertDatabaseHas('cash_movements', ['category' => 'refund', 'amount' => 100]);
        $this->assertSame(1, StockMovement::query()->where('type', StockMovement::TYPE_RETURN)->count());
        $this->assertDatabaseHas('stock_movements', ['product_id' => $data['filter']->id, 'type' => StockMovement::TYPE_RETURN, 'quantity' => 1]);
    }

    #[TestWith([true, false])]
    #[TestWith([false, true])]
    public function test_pending_kit_resolves_current_tracking_at_charge_and_return_uses_that_actual_effect(bool $trackedWhenSaved, bool $trackedWhenCharged): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $component = Product::factory()->create(['stock' => 10, 'track_inventory' => $trackedWhenSaved]);
        $kit = Product::factory()->create([
            'sale_mode' => Product::MODE_KIT, 'kit_deduct_components' => true,
            'stock' => 0, 'min_stock' => 0, 'track_inventory' => false, 'price' => 100,
        ]);
        $kit->kitItems()->attach($component->id, ['quantity' => 2]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'pending-track-return', 'status' => 'pending',
            'items' => [['product_id' => $kit->id, 'quantity' => 3]],
        ]);
        $item = $sale->items->sole();
        $this->assertNull($item->inventory_snapshot['effects']);
        $this->assertSame(10, $component->fresh()->stock);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $component->update(['track_inventory' => $trackedWhenCharged]);
        $this->actingAs($employee)->postJson(route('pos.sales.charge', $sale), [])->assertOk();
        $this->assertSame($trackedWhenCharged ? [
            ['product_id' => $component->id, 'product_name' => $component->name, 'quantity' => 6],
        ] : [], $item->fresh()->inventory_snapshot['effects']);
        $this->assertSame($trackedWhenCharged ? 4 : 10, $component->fresh()->stock);
        $component->update(['track_inventory' => ! $trackedWhenCharged]);
        $return = $this->requestItemReturn($employee, $sale->fresh(), $item, 1);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($trackedWhenCharged ? 6 : 10, $component->fresh()->stock);
        $this->assertSame($trackedWhenCharged ? 1 : 0, StockMovement::query()->where('type', StockMovement::TYPE_RETURN)->count());
        $this->assertSame('100.00', $return->fresh()->total);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'category' => 'refund', 'amount' => 100]);
    }

    public function test_bulk_returns_preserve_request_and_approval_permissions(): void
    {
        $employee = $this->employee();
        $product = Product::factory()->bulk()->create(['stock' => 1, 'price' => 10]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'bulk-return-permissions',
            'items' => [['product_id' => $product->id, 'quantity' => 0.5]],
        ]);
        $item = $sale->items->sole();
        $unauthorized = User::factory()->create()->assignRole('empleado');
        $unauthorized->givePermissionTo(['pos.access', 'pos.return.do']);

        $this->actingAs($unauthorized)->postJson(route('pos.returns.store'), [
            'sale_id' => $sale->id, 'reason' => 'Sin permiso',
            'items' => [['sale_item_id' => $item->id, 'quantity' => 0.125]],
        ])->assertForbidden();

        $this->assertDatabaseCount('sale_returns', 0);
        $return = $this->requestItemReturn($employee, $sale, $item, 0.125);
        $this->actingAs($employee)->putJson(route('pos.returns.approve', $return))->assertForbidden();
        $this->assertSame(SaleReturn::STATUS_PENDING, $return->fresh()->status);
        $this->assertSame(0, $item->fresh()->returned_quantity);
        $this->assertSame(0.5, $product->fresh()->stock);
        $this->assertSame(0, CashMovement::query()->where('category', 'refund')->count());
        $this->assertSame(0, StockMovement::query()->where('type', StockMovement::TYPE_RETURN)->count());
    }

    public function test_employee_can_request_return_for_paid_sale(): void
    {
        $employee = $this->employee();
        $data = $this->paidSaleWithProduct($employee);

        $response = $this->actingAs($employee)->postJson(route('pos.returns.store'), [
            'sale_id' => $data['sale']->id,
            'reason' => 'Producto defectuoso',
            'items' => [
                [
                    'sale_item_id' => $data['saleItem']->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect();

        $return = SaleReturn::where('sale_id', $data['sale']->id)->first();
        $this->assertNotNull($return);
        $this->assertEquals('pending', $return->status);
        $this->assertEquals(100.00, (float) $return->total);
        // returned_quantity se incrementa al aprobar la devolución, no al solicitarla.
        $this->assertEquals(0, $data['saleItem']->refresh()->returned_quantity);
    }

    public function test_cannot_return_more_than_available_quantity(): void
    {
        $employee = $this->employee();
        $data = $this->paidSaleWithProduct($employee, 1);

        $response = $this->actingAs($employee)->postJson(route('pos.returns.store'), [
            'sale_id' => $data['sale']->id,
            'reason' => 'Prueba',
            'items' => [
                [
                    'sale_item_id' => $data['saleItem']->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_approve_return_and_update_stock_and_cash(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $admin->givePermissionTo(['pos.access', 'pos.sell', 'pos.returns.request', 'pos.returns.approve']);

        $employee = $this->employee();
        $data = $this->paidSaleWithProduct($employee);

        $this->actingAs($employee)->postJson(route('pos.returns.store'), [
            'sale_id' => $data['sale']->id,
            'reason' => 'Producto defectuoso',
            'items' => [
                [
                    'sale_item_id' => $data['saleItem']->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $return = SaleReturn::first();

        $response = $this->actingAs($admin)->putJson(route('pos.returns.approve', ['saleReturn' => $return]));

        $response->assertRedirect();

        $return->refresh();
        $this->assertEquals('approved', $return->status);

        $data['product']->refresh();
        $this->assertEquals(11, $data['product']->stock); // Se reabasteció 1
        $this->assertEquals(1, $data['saleItem']->refresh()->returned_quantity);

        $this->assertDatabaseHas('cash_movements', [
            'category' => 'refund',
            'amount' => 100.00,
            'type' => 'out',
        ]);
    }

    public function test_return_after_clearing_inventory_preserves_payment_and_stock_history_without_recreating_product(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');
        $employee = $this->employee();
        $product = Product::factory()->create([
            'name' => 'Producto para devolución',
            'sku' => 'DEV-001',
            'barcode' => '00012345',
            'barcode2' => '00(DEV)+123',
            'price' => 100,
            'cost' => 60,
            'stock' => 10,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ]);
        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'return-cleared-inventory',
            'status' => 'paid',
            'amount_paid' => 200,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $saleItem = $sale->items()->sole();
        $payment = CashMovement::query()->where('sale_id', $sale->id)->sole();
        $stockMovement = StockMovement::query()->where('sale_id', $sale->id)->sole();
        $saleBefore = $sale->getAttributes();
        $itemBefore = $saleItem->getAttributes();
        $paymentBefore = $payment->getAttributes();
        $movementBefore = $stockMovement->getAttributes();
        $this->assertSame('paid', $sale->status);
        $this->assertSame('200.00', $sale->amount_paid);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseCount('daily_cuts', 0);

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se eliminaron 1 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->assertSame([...$itemBefore, 'product_id' => null], $saleItem->fresh()->getAttributes());
        $this->actingAs($employee)->postJson(route('pos.returns.store'), [
            'sale_id' => $sale->id,
            'reason' => 'Producto defectuoso después de limpiar el inventario',
            'items' => [['sale_item_id' => $saleItem->id, 'quantity' => 1]],
        ])->assertRedirect(route('pos.returns.index'))->assertSessionHasNoErrors();
        $return = SaleReturn::query()->where('sale_id', $sale->id)->sole();
        $this->assertSame(SaleReturn::STATUS_PENDING, $return->status);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))
            ->assertRedirect(route('pos.returns.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sale_returns', [
            'id' => $return->id,
            'sale_id' => $sale->id,
            'requested_by' => $employee->id,
            'approved_by' => $admin->id,
            'status' => SaleReturn::STATUS_APPROVED,
            'total' => 100,
            'approved_at' => now()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('sale_return_items', [
            'sale_return_id' => $return->id,
            'sale_item_id' => $saleItem->id,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'restock' => true,
        ]);
        $this->assertSame($saleBefore, $sale->fresh()->getAttributes());
        $this->assertSame($paymentBefore, $payment->fresh()->getAttributes());
        $this->assertDatabaseCount('cash_movements', 2);
        $this->assertDatabaseHas('cash_movements', [
            'sale_id' => $sale->id,
            'user_id' => $admin->id,
            'category' => 'refund',
            'amount' => 100,
            'type' => 'out',
            'happened_at' => now()->toDateTimeString(),
        ]);
        $this->assertSame(
            Arr::except([...$itemBefore, 'product_id' => null], ['returned_quantity']),
            Arr::except($saleItem->fresh()->getAttributes(), ['returned_quantity']),
        );
        $this->assertSame(1, $saleItem->fresh()->returned_quantity);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(
            Arr::except($movementBefore, ['product_id', 'product_snapshot']),
            Arr::except($stockMovement->fresh()->getAttributes(), ['product_id', 'product_snapshot']),
        );
        $stockMovement->refresh();
        $this->assertNull($stockMovement->product_id);
        $this->assertSame([
            'id' => $product->id,
            'name' => 'Producto para devolución',
            'sku' => 'DEV-001',
            'barcode' => '00012345',
            'barcode2' => '00(DEV)+123',
        ], $stockMovement->product_snapshot);
    }

    public function test_approval_does_not_change_a_globally_closed_day(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidSaleWithProduct($this->employee());
        $return = $this->pendingReturn($admin, $data);
        app(DailyCutService::class)->generate(today(), $admin);
        DailyCut::query()->where('scope', DailyCut::SCOPE_GLOBAL)->update(['closed_at' => now()]);

        $this->actingAs($admin)->putJson(route('pos.returns.approve', $return))
            ->assertUnprocessable()->assertJsonValidationErrors('return');

        $this->assertSame(SaleReturn::STATUS_PENDING, $return->fresh()->status);
        $this->assertSame(10, $data['product']->fresh()->stock);
        $this->assertSame(0, $data['saleItem']->fresh()->returned_quantity);
        $this->assertSame(0, CashMovement::query()->where('category', 'refund')->count());
    }

    public function test_reject_rechecks_a_stale_pending_return_after_it_was_approved(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidSaleWithProduct($this->employee());
        $return = $this->pendingReturn($admin, $data);
        $staleReturn = $return->fresh();
        $service = app(ReturnService::class);
        $service->approve($return, $admin);

        try {
            $service->reject($staleReturn, $admin);
            $this->fail('Una devolución aprobada no puede rechazarse con un modelo obsoleto.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Esta devolución ya fue procesada.'], $exception->errors()['return']);
        }

        $this->assertSame(SaleReturn::STATUS_APPROVED, $return->fresh()->status);
        $this->assertSame(11, $data['product']->fresh()->stock);
        $this->assertSame(1, CashMovement::query()->where('category', 'refund')->count());
    }

    public function test_approve_rechecks_a_stale_pending_return_after_it_was_rejected(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidSaleWithProduct($this->employee());
        $return = $this->pendingReturn($admin, $data);
        $staleReturn = $return->fresh();
        $service = app(ReturnService::class);
        $service->reject($return, $admin);

        try {
            $service->approve($staleReturn, $admin);
            $this->fail('Una devolución rechazada no puede aprobarse con un modelo obsoleto.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Esta devolución ya fue procesada.'], $exception->errors()['return']);
        }

        $this->assertSame(SaleReturn::STATUS_REJECTED, $return->fresh()->status);
        $this->assertSame(10, $data['product']->fresh()->stock);
        $this->assertSame(0, CashMovement::query()->where('category', 'refund')->count());
    }

    #[TestWith(['approve'])]
    #[TestWith(['generate'])]
    #[TestWith(['close'])]
    public function test_day_lock_blocks_competing_approval_generation_and_close(string $operation): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');
        $data = $this->paidSaleWithProduct($this->employee());
        $return = $this->pendingReturn($admin, $data);
        $lock = Cache::store('database')->lock('pos-day:'.today()->toDateString(), 120);
        $this->assertTrue($lock->get());
        $this->actingAs($admin);

        try {
            $response = match ($operation) {
                'approve' => $this->putJson(route('pos.returns.approve', $return)),
                'generate' => $this->postJson(route('pos.cuts.store'), ['date' => today()->toDateString()]),
                'close' => $this->postJson(route('pos.cuts.close-day'), ['force' => true]),
            };
            $response->assertUnprocessable()->assertJsonValidationErrors('day');
        } finally {
            $lock->release();
        }

        $this->assertDatabaseCount('daily_cuts', 0);
        $this->assertSame(SaleReturn::STATUS_PENDING, $return->fresh()->status);
        $this->assertSame(10, $data['product']->fresh()->stock);
        $this->assertSame(0, CashMovement::query()->where('category', 'refund')->count());
    }

    /**
     * @param  array{sale: Sale, saleItem: SaleItem, product: Product}  $data
     */
    private function pendingReturn(User $requester, array $data): SaleReturn
    {
        return app(ReturnService::class)->request($requester, [
            'sale_id' => $data['sale']->id,
            'reason' => 'Producto defectuoso',
            'items' => [['sale_item_id' => $data['saleItem']->id, 'quantity' => 1]],
        ]);
    }
}
