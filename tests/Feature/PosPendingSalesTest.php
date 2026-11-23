<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosPendingSalesTest extends TestCase
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

    private function pendingSale(User $cashier, string $sku): Sale
    {
        $product = Product::factory()->create([
            'sku' => $sku,
            'price' => 100.00,
            'stock' => 10,
            'track_inventory' => true,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-'.$sku,
            'status' => 'pending',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertOk();

        return Sale::query()->findOrFail($response->json('sale.id'));
    }

    public function test_pending_list_only_returns_tickets_visible_to_the_cashier(): void
    {
        $cashier = $this->employee();
        $mine = $this->pendingSale($cashier, 'PEND-MIO');
        $this->pendingSale($this->employee(), 'PEND-AJENO');

        $this->actingAs($cashier)->getJson(route('pos.sales.pendingList'))
            ->assertOk()
            ->assertJsonCount(1, 'sales')
            ->assertJsonPath('sales.0.id', $mine->id)
            ->assertJsonPath('sales.0.item_count', 1)
            ->assertJsonPath('sales.0.total', 100);

        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->getJson(route('pos.sales.pendingList'))
            ->assertOk()
            ->assertJsonCount(2, 'sales');
    }

    public function test_cashier_can_cancel_a_pending_ticket_without_deleting_its_history(): void
    {
        $cashier = $this->employee(['pos.cancel.sale']);
        $sale = $this->pendingSale($cashier, 'PEND-CANCEL');

        $this->actingAs($cashier)->deleteJson(route('pos.sales.cancel', $sale))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(Sale::STATUS_CANCELLED, $sale->fresh()->status);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => Sale::STATUS_CANCELLED]);

        $this->actingAs($cashier)->getJson(route('pos.sales.pendingList'))
            ->assertOk()
            ->assertJsonCount(0, 'sales');

        $withoutPermission = $this->employee();
        $otherSale = $this->pendingSale($withoutPermission, 'PEND-SIN-PERM');

        $this->actingAs($withoutPermission)->deleteJson(route('pos.sales.cancel', $otherSale))
            ->assertForbidden();
    }

    public function test_pos_screen_exposes_last_sale_payment_and_pending_tickets_dialog(): void
    {
        $this->actingAs($this->employee())->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Pago con:')
            ->assertSee('Cambio:')
            ->assertSee('Reimprimir último ticket')
            ->assertSee('Tickets pendientes')
            ->assertSee('pendingList', false);
    }
}
