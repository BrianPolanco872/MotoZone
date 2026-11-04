<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user->givePermissionTo(array_merge(['pos.access', 'pos.sell', 'pos.returns.request'], $permissions));

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

    /** @test */
    public function employee_can_request_return_for_paid_sale(): void
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
        $this->assertEquals(1, $data['saleItem']->refresh()->returned_quantity);
    }

    /** @test */
    public function cannot_return_more_than_available_quantity(): void
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

    /** @test */
    public function admin_can_approve_return_and_update_stock_and_cash(): void
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

        $response = $this->actingAs($admin)->postJson(route('pos.returns.approve', $return));

        $response->assertRedirect();

        $return->refresh();
        $this->assertEquals('approved', $return->status);

        $data['product']->refresh();
        $this->assertEquals(11, $data['product']->stock); // Se reabasteció 1

        $this->assertDatabaseHas('cash_movements', [
            'category' => 'refund',
            'amount' => 100.00,
            'type' => 'out',
        ]);
    }
}
