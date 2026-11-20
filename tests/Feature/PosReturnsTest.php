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
        $this->assertSame([
            ...$itemBefore, 'product_id' => null, 'returned_quantity' => 1,
        ], $saleItem->fresh()->getAttributes());
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
