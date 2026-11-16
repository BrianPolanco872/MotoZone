<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCashTest extends TestCase
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
        $user->givePermissionTo(array_merge(['pos.access', 'pos.cash.manage'], $permissions));

        return $user;
    }

    public function test_employee_can_register_cash_movement(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial de caja',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('cash_movements', [
            'user_id' => $employee->id,
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial de caja',
        ]);
    }

    public function test_cash_movement_requires_valid_category(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'invalid_category',
            'amount' => 100.00,
            'concept' => 'Prueba',
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('cash_movements', [
            'category' => 'invalid_category',
        ]);
    }

    public function test_cash_movement_requires_positive_amount(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 0,
            'concept' => 'Prueba',
        ]);

        $response->assertUnprocessable();
    }

    public function test_employee_can_register_supplier_payment_as_cash_out(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'supplier_payment',
            'amount' => 350.50,
            'method' => 'cash',
            'supplier_name' => 'Refacciones del Norte',
            'reason' => 'Compra de refacciones',
            'concept' => 'Pago a proveedor',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('supplier_payments', [
            'user_id' => $employee->id,
            'amount' => 350.50,
            'method' => 'cash',
            'supplier_name' => 'Refacciones del Norte',
            'reason' => 'Compra de refacciones',
        ]);
    }

    public function test_employee_can_register_customer_payment(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'customer_payment',
            'amount' => 200.00,
            'method' => 'transfer',
            'customer_name' => 'Cliente mostrador',
            'concept' => 'Abono de cliente',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('customer_payments', [
            'user_id' => $employee->id,
            'amount' => 200.00,
            'method' => 'transfer',
            'customer_name' => 'Cliente mostrador',
        ]);
    }

    public function test_pos_shows_start_day_button_before_initial_cash(): void
    {
        $employee = $this->employee(['pos.cut.own']);

        $response = $this->actingAs($employee)->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('Iniciar día');
    }

    public function test_pos_hides_start_day_button_after_initial_cash(): void
    {
        $employee = $this->employee(['pos.cut.own']);

        CashMovement::create([
            'user_id' => $employee->id,
            'type' => CashMovement::TYPE_IN,
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Efectivo inicial',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($employee)->get(route('pos.index'));

        $response->assertOk();
        $response->assertDontSee('Iniciar día');
    }

    public function test_start_day_button_is_hidden_when_global_day_is_closed(): void
    {
        $employee = $this->employee(['pos.cut.own']);

        DailyCut::create([
            'cut_key' => DailyCut::keyFor(now()->toDateString(), DailyCut::SCOPE_GLOBAL),
            'cut_date' => now()->toDateString(),
            'scope' => DailyCut::SCOPE_GLOBAL,
            'user_id' => null,
            'version' => 1,
            'totals' => [],
            'generated_by' => $employee->id,
            'generated_at' => now(),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($employee)->get(route('pos.index'));

        $response->assertOk();
        $response->assertDontSee('Iniciar día');
    }

    public function test_initial_cash_is_reflected_in_employee_cut(): void
    {
        $employee = $this->employee(['pos.cut.own']);

        $this->actingAs($employee)->post(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Efectivo inicial',
        ])->assertRedirect();

        $response = $this->actingAs($employee)->get(route('pos.cuts.mine'));

        $response->assertOk();
        $response->assertSee('Dinero inicial en caja');
        $response->assertSee('$500.00');
    }

    public function test_initial_cash_can_only_be_registered_once_per_day(): void
    {
        $employee = $this->employee(['pos.cut.own']);

        $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Efectivo inicial',
        ])->assertRedirect();

        $response = $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 300.00,
            'concept' => 'Efectivo inicial',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('amount');

        $this->assertSame(1, CashMovement::query()
            ->where('user_id', $employee->id)
            ->where('category', 'initial')
            ->count());
    }

    public function test_another_employee_can_register_own_initial_cash_same_day(): void
    {
        $employeeA = $this->employee();
        $employeeB = $this->employee();

        $this->actingAs($employeeA)->postJson(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Efectivo inicial',
        ])->assertRedirect();

        $this->actingAs($employeeB)->postJson(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 300.00,
            'concept' => 'Efectivo inicial',
        ])->assertRedirect();

        $this->assertSame(2, CashMovement::query()
            ->where('category', 'initial')
            ->whereBetween('happened_at', [now()->startOfDay(), now()->endOfDay()])
            ->count());
    }
}
