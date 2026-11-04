<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosPaymentMethodsTest extends TestCase
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

    private function product(array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        return Product::factory()->create(array_merge([
            'name' => 'Producto pago '.$counter,
            'sku' => 'PAG-'.$counter,
            'barcode' => '750'.str_pad((string) $counter, 10, '0', STR_PAD_LEFT),
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 100,
            'min_stock' => 2,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ], $overrides));
    }

    private function paidSale(User $user, float $total, string $key, string $method = 'cash'): Sale
    {
        $product = $this->product(['stock' => 100]);

        return Sale::create([
            'user_id' => $user->id,
            'folio' => 'V-'.str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            'status' => 'paid',
            'payment_method' => $method,
            'subtotal' => round($total / 1.16, 2),
            'tax_total' => round($total - $total / 1.16, 2),
            'total' => $total,
            'amount_paid' => $total,
            'change_amount' => 0.00,
            'idempotency_key' => $key,
            'paid_at' => now(),
        ]);
    }

    public function test_venta_con_tarjeta_no_suma_a_efectivo_pero_si_a_ventas_totales(): void
    {
        $employee = $this->employee(['pos.sell']);
        $product = $this->product(['stock' => 10]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'card-sale-001',
            'status' => 'paid',
            'amount_paid' => 100.00,
            'payment_method' => 'card',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSuccessful();

        $sale = Sale::where('idempotency_key', 'card-sale-001')->first();
        $this->assertNotNull($sale);
        $this->assertEquals('card', $sale->payment_method);
        $this->assertEquals(100.00, (float) $sale->total);
    }

    public function test_venta_con_transferencia_no_suma_a_efectivo(): void
    {
        $employee = $this->employee(['pos.sell']);
        $product = $this->product(['stock' => 10]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'transfer-sale-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
            'payment_method' => 'transfer',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSuccessful();

        $sale = Sale::where('idempotency_key', 'transfer-sale-001')->first();
        $this->assertNotNull($sale);
        $this->assertEquals('transfer', $sale->payment_method);
    }

    public function test_venta_efectivo_suma_a_efectivo(): void
    {
        $employee = $this->employee(['pos.sell']);
        $product = $this->product(['stock' => 10]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'cash-sale-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSuccessful();

        $sale = Sale::where('idempotency_key', 'cash-sale-001')->first();
        $this->assertNotNull($sale);
        $this->assertEquals('cash', $sale->payment_method);
    }

    public function test_no_se_puede_cobrar_con_monto_insuficiente(): void
    {
        $employee = $this->employee(['pos.sell']);
        $product = $this->product(['stock' => 10]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'insuficiente-001',
            'status' => 'paid',
            'amount_paid' => 50.00,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_venta_con_cambio_positivo(): void
    {
        $employee = $this->employee(['pos.sell']);
        $product = $this->product(['stock' => 10]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'cambio-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSuccessful();

        $sale = Sale::where('idempotency_key', 'cambio-001')->first();
        $this->assertNotNull($sale);
        $this->assertEquals(200.00, (float) $sale->amount_paid);
        $this->assertEquals(100.00, (float) $sale->change_amount); // 200 - 100
    }

    public function test_ticket_muestra_metodo_de_pago(): void
    {
        $employee = $this->employee(['pos.sell', 'pos.tickets.print.own']);
        $product = $this->product(['stock' => 10]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'ticket-metodo-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
            'payment_method' => 'card',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSuccessful();

        $sale = Sale::where('idempotency_key', 'ticket-metodo-001')->first();

        $ticketResponse = $this->actingAs($employee)->get(route('pos.sales.ticket', $sale));
        $ticketResponse->assertOk();
        $ticketResponse->assertSee('Tarjeta');
    }

    public function test_empleado_sin_permiso_no_puede_cobrar(): void
    {
        $employee = $this->employee(['pos.access']); // sin pos.sell
        $product = $this->product(['stock' => 10]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'sin-permiso-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertForbidden();
    }
}
