<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\SupplierPayment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCutSupplierPaymentsDetailTest extends TestCase
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
        $user->givePermissionTo(['pos.access', 'pos.cash.manage', 'pos.cut.own']);

        return $user;
    }

    private function cashOut(User $user, string $category, string $concept, float $amount): CashMovement
    {
        return CashMovement::create([
            'user_id' => $user->id,
            'type' => CashMovement::TYPE_OUT,
            'category' => $category,
            'amount' => $amount,
            'concept' => $concept,
            'happened_at' => now(),
        ]);
    }

    private function supplierPayment(User $user, string $supplier, float $amount): SupplierPayment
    {
        return SupplierPayment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'method' => 'cash',
            'reason' => 'Compra de refacciones',
            'supplier_name' => $supplier,
            'happened_at' => now(),
        ]);
    }

    public function test_employee_can_see_own_cash_outs_in_supplier_payments_detail(): void
    {
        $employee = $this->employee();

        $this->cashOut($employee, 'expense', 'Compra de papelería', 250.00);
        $this->cashOut($employee, 'withdrawal', 'Retiro para depósito', 150.00);
        $this->supplierPayment($employee, 'Proveedor Central', 500.00);

        $response = $this->actingAs($employee)->getJson(route('pos.cuts.supplier-payments-detail', [
            'date' => today()->toDateString(),
            'employee' => $employee->id,
        ]));

        $response->assertOk()
            ->assertJsonCount(3, 'items')
            ->assertJsonPath('total', 900)
            ->assertJsonFragment([
                'descripcion' => 'Compra de papelería',
                'categoria' => 'Gasto',
                'monto' => 250,
            ])
            ->assertJsonFragment([
                'descripcion' => 'Retiro para depósito',
                'categoria' => 'Retiro de efectivo',
                'monto' => 150,
            ])
            ->assertJsonFragment([
                'descripcion' => 'Proveedor Central',
                'categoria' => 'Pago a proveedor',
                'monto' => 500,
            ]);
    }

    public function test_employee_cannot_see_another_employee_cash_outs(): void
    {
        $employee = $this->employee();
        $otherEmployee = $this->employee();

        $this->cashOut($otherEmployee, 'expense', 'Gasto ajeno', 99.00);

        $this->actingAs($employee)
            ->getJson(route('pos.cuts.supplier-payments-detail', [
                'date' => today()->toDateString(),
                'employee' => $otherEmployee->id,
            ]))
            ->assertForbidden();
    }

    public function test_cut_page_shows_cash_out_total_in_supplier_payments_row(): void
    {
        $employee = $this->employee();

        $this->cashOut($employee, 'expense', 'Compra de limpieza', 320.00);

        $this->actingAs($employee)
            ->get(route('pos.cuts.mine'))
            ->assertOk()
            ->assertSee('Pagos a proveedores / salidas')
            ->assertSee('$320.00');
    }
}
