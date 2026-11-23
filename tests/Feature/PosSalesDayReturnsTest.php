<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSalesDayReturnsTest extends TestCase
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

    private function paidSale(User $cashier, string $sku): Sale
    {
        $product = Product::factory()->create([
            'sku' => $sku,
            'name' => 'Producto '.$sku,
            'price' => 100.00,
            'stock' => 10,
            'track_inventory' => true,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'day-returns-'.$sku,
            'status' => 'paid',
            'amount_paid' => 1000.00,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk();

        return Sale::query()->findOrFail($response->json('sale.id'));
    }

    public function test_day_list_only_returns_the_current_users_sales_and_supports_search(): void
    {
        $cashier = $this->employee();
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->paidSale($cashier, 'DAY-ABC');
        $this->paidSale($this->employee(), 'DAY-XYZ');

        $this->actingAs($cashier)->getJson(route('pos.sales.dayList'))
            ->assertOk()
            ->assertJsonCount(1, 'sales')
            ->assertJsonPath('sales.0.id', $sale->id)
            ->assertJsonPath('sales.0.code', $sale->folio)
            ->assertJsonPath('sales.0.client', $cashier->name)
            ->assertJsonPath('sales.0.cashier', $cashier->name)
            ->assertJsonPath('sales.0.item_count', 1)
            ->assertJsonPath('sales.0.items.0.sku', 'DAY-ABC');

        $this->actingAs($cashier)->getJson(route('pos.sales.dayList', ['search' => 'DAY-XYZ']))
            ->assertOk()
            ->assertJsonCount(0, 'sales');

        $ownAdminSale = $this->paidSale($admin, 'DAY-ADM');

        $this->actingAs($admin)->getJson(route('pos.sales.dayList'))
            ->assertOk()
            ->assertJsonCount(1, 'sales')
            ->assertJsonPath('sales.0.id', $ownAdminSale->id);
    }

    public function test_returning_one_item_restores_stock_and_recalculates_total(): void
    {
        $cashier = $this->employee(['pos.returns.request', 'pos.return.do']);
        $sale = $this->paidSale($cashier, 'RET-001');
        $item = $sale->items()->sole();
        $product = Product::query()->findOrFail($item->product_id);
        $stockBefore = (float) $product->stock;
        $totalBefore = (float) $sale->total;

        $this->actingAs($cashier)->postJson(route('pos.sales.returnItem', ['sale' => $sale, 'saleItem' => $item]))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame($stockBefore + (float) $item->quantity, (float) $product->fresh()->stock);
        $this->assertSame((float) $item->quantity, (float) $item->fresh()->returned_quantity);
        $this->assertDatabaseHas('sale_returns', ['sale_id' => $sale->id, 'status' => 'approved']);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'type' => 'out', 'category' => 'refund']);

        $response = $this->actingAs($cashier)->getJson(route('pos.sales.dayList'))
            ->assertOk();

        $this->assertEquals(round($totalBefore - (float) $item->line_total, 2), $response->json('sales.0.total'));
    }

    public function test_returning_item_requires_return_permissions(): void
    {
        $cashier = $this->employee();
        $sale = $this->paidSale($cashier, 'RET-NO');
        $item = $sale->items()->sole();

        $this->actingAs($cashier)->postJson(route('pos.sales.returnItem', ['sale' => $sale, 'saleItem' => $item]))
            ->assertForbidden();
    }

    public function test_cancelling_a_paid_sale_returns_every_item_and_marks_it_cancelled(): void
    {
        $cashier = $this->employee(['pos.cancel.sale']);
        $sale = $this->paidSale($cashier, 'CAN-001');
        $item = $sale->items()->sole();
        $product = Product::query()->findOrFail($item->product_id);
        $stockBefore = (float) $product->stock;

        $this->actingAs($cashier)->deleteJson(route('pos.sales.cancel', $sale))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(Sale::STATUS_CANCELLED, $sale->fresh()->status);
        $this->assertSame($stockBefore + (float) $item->quantity, (float) $product->fresh()->stock);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'type' => 'out']);

        $this->actingAs($cashier)->getJson(route('pos.sales.dayList'))
            ->assertOk()
            ->assertJsonPath('sales.0.cancelled', true)
            ->assertJsonPath('sales.0.total', 0);

        $this->actingAs($cashier)->deleteJson(route('pos.sales.cancel', $sale))
            ->assertUnprocessable();
    }

    public function test_pos_screen_exposes_day_sales_button_and_modal(): void
    {
        $this->actingAs($this->employee())->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Ventas del día y devoluciones')
            ->assertSee('Devolver Artículo seleccionado')
            ->assertSee('Cancelar Venta')
            ->assertSee('Imprimir copia')
            ->assertSee('dayList', false)
            ->assertDontSee('Cajero:');
    }
}
