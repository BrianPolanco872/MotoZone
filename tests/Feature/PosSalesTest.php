<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** @test */
    public function employee_can_create_paid_sale_and_deducts_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'test-sale-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertCreated();

        $sale = Sale::where('idempotency_key', 'test-sale-001')->first();
        $this->assertNotNull($sale);
        $this->assertNotNull($sale->folio);
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals(200.00, (float) $sale->total);
        $this->assertEquals(116.00, (float) $sale->amount_paid);

        $product->refresh();
        $this->assertEquals(3, $product->stock);
    }

    /** @test */
    public function idempotency_prevents_duplicate_sale(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'idem-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
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
            'amount_paid' => 116.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertCreated();

        $this->assertCount(1, Sale::where('idempotency_key', 'idem-001')->get());
        $product->refresh();
        $this->assertEquals(3, $product->stock); // Solo se descontó una vez
    }

    /** @test */
    public function sale_cannot_exceed_available_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 1]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'stock-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
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
    }

    /** @test */
    public function pending_sale_can_be_charged_and_updates_stock(): void
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

        $chargeResponse->assertCreated();

        $this->assertEquals(3, $product->refresh()->stock); // Ahora se descuenta

        $sale = Sale::find($saleId);
        $this->assertEquals('paid', $sale->status);
        $this->assertNotNull($sale->paid_at);
    }
}
