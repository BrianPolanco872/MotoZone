<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function employeeWithCut(array $permissions = []): User
    {
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo(array_merge(['pos.access', 'pos.sell', 'pos.cut.manage'], $permissions));

        return $user;
    }

    private function makePaidSale(User $user, float $total, string $key): Sale
    {
        $product = Product::factory()->create([
            'name' => 'Producto corte',
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 100,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ]);

        return Sale::create([
            'user_id' => $user->id,
            'folio' => 'V-'.str_pad((string) random_int(1000, 9999), 6, '0', STR_PAD_LEFT),
            'status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => round($total / 1.16, 2),
            'tax_total' => round($total - $total / 1.16, 2),
            'total' => $total,
            'amount_paid' => $total,
            'change_amount' => 0.00,
            'idempotency_key' => $key,
            'paid_at' => now(),
        ]);
    }

    /** @test */
    public function global_cut_includes_all_employees_sales(): void
    {
        $admin = $this->employeeWithCut();
        $employeeA = User::factory()->create()->assignRole('empleado');
        $employeeA->givePermissionTo(['pos.access', 'pos.sell']);
        $employeeB = User::factory()->create()->assignRole('empleado');
        $employeeB->givePermissionTo(['pos.access', 'pos.sell']);

        $this->makePaidSale($employeeA, 200.00, 'cut-a-1');
        $this->makePaidSale($employeeB, 150.00, 'cut-a-2');

        CashMovement::create([
            'user_id' => $employeeA->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('$350.00'); // Ventas totales
        $response->assertSee('$500.00'); // Dinero inicial
    }

    /** @test */
    public function cut_can_be_generated_and_persisted(): void
    {
        $admin = $this->employeeWithCut();

        $this->makePaidSale($admin, 200.00, 'cut-gen-1');

        $response = $this->actingAs($admin)->post(route('pos.cuts.store'), [
            'date' => now()->toDateString(),
            'notes' => 'Corte de prueba',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('daily_cuts', [
            'scope' => 'global',
            'cut_date' => now()->toDateString(),
        ]);

        $cut = DailyCut::where('scope', 'global')->where('cut_date', now()->toDateString())->first();
        $this->assertNotNull($cut);
        $this->assertEquals('Corte de prueba', $cut->notes);
    }

    /** @test */
    public function employee_can_see_own_cut(): void
    {
        $employee = $this->employeeWithCut(['pos.sales.day.own']);

        $this->makePaidSale($employee, 100.00, 'cut-own-1');

        $response = $this->actingAs($employee)->get(route('pos.cuts.mine', ['date' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('Mi corte');
        $response->assertSee('$100.00');
    }

    /** @test */
    public function product_with_cost_is_reflected_in_cut_profit(): void
    {
        $admin = $this->employeeWithCut();

        $product = Product::factory()->create([
            'name' => 'Producto con costo',
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 100,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ]);

        $sale = $this->makePaidSale($admin, 100.00, 'cut-profit-1');

        SaleItem::where('sale_id', $sale->id)->delete();

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'category_name' => $product->category?->name,
            'quantity' => 1,
            'unit_price' => 100.00,
            'cost_price' => 60.00,
            'tax_rate' => 0.16,
            'line_subtotal' => 86.21,
            'tax_amount' => 13.79,
            'line_total' => 100.00,
            'line_cost' => 60.00,
            'price_overridden' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('$100.00'); // Ventas
        $response->assertSee('$40.00'); // Ganancia aproximada (100 - 60 - IVA...)
    }
}
