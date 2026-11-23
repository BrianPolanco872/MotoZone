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

    private function employee(): User
    {
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo(['pos.access', 'pos.sell']);

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
