<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCashArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(array $permissions = []): User
    {
        $user = User::factory()->create()->assignRole('admin');
        $user->givePermissionTo(array_merge(['pos.access', 'pos.cash.manage', 'pos.sales.day.any'], $permissions));

        return $user;
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

        $response = $this->actingAs($employee)->post(route('pos.cash.store'), [
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

    public function test_cash_movement_requires_positive_amount(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->post(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 0,
            'concept' => 'Prueba',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_cash_movement_requires_concept(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->post(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 100.00,
            'concept' => '',
        ]);

        $response->assertSessionHasErrors('concept');
    }

    public function test_admin_can_archive_movement_soft(): void
    {
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('pos.cash.archive', $movement), [
            'confirm' => true,
        ]);

        $response->assertRedirect();

        $movement->refresh();
        $this->assertNotNull($movement->archivado_at);
        $this->assertEquals($admin->id, $movement->archivado_por);
    }

    public function test_archived_movement_not_in_main_view(): void
    {
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial',
            'happened_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('pos.cash.archive', $movement), ['confirm' => true]);

        $response = $this->actingAs($admin)->get(route('pos.cash.index'));

        $response->assertOk();
        // El movimiento archivado no debe aparecer en la vista principal
        $response->assertDontSee('$500.00');
        $response->assertSee('0 movimiento(s)');
    }

    public function test_archived_movement_in_archived_view(): void
    {
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial',
            'happened_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('pos.cash.archive', $movement), ['confirm' => true]);

        $response = $this->actingAs($admin)->get(route('pos.cash.index', ['show_archived' => true]));

        $response->assertOk();
        $response->assertSee('Dinero inicial');
    }

    public function test_admin_can_restore_archived_movement(): void
    {
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial',
            'happened_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('pos.cash.archive', $movement), ['confirm' => true]);

        $response = $this->actingAs($admin)->post(route('pos.cash.restore', $movement));

        $response->assertRedirect();

        $movement->refresh();
        $this->assertNull($movement->archivado_at);
        $this->assertNull($movement->archivado_por);
    }

    public function test_cannot_delete_movement_in_closed_cut(): void
    {
        $admin = $this->admin();

        // Crear una venta pagada con paid_at hoy
        $sale = Sale::create([
            'user_id' => $admin->id,
            'folio' => 'V-000001',
            'status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 100.00,
            'tax_total' => 0.00,
            'total' => 100.00,
            'amount_paid' => 100.00,
            'change_amount' => 0.00,
            'idempotency_key' => 'closed-cut-test-'.uniqid(),
            'paid_at' => now(),
        ]);

        // Movimiento de caja ligado a la venta (categoría 'sale')
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'sale_id' => $sale->id,
            'type' => 'in',
            'category' => 'sale',
            'amount' => 100.00,
            'concept' => 'Venta '.$sale->folio,
            'happened_at' => now(),
        ]);

        // Simular que hay un corte cerrado para hoy
        DailyCut::create([
            'cut_key' => now()->toDateString().'|global',
            'cut_date' => now()->toDateString(),
            'scope' => 'global',
            'user_id' => null,
            'version' => 1,
            'totals' => [],
            'generated_by' => $admin->id,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('pos.cash.destroy', $movement), [
            'deletion_reason' => 'Prueba de eliminación',
        ]);

        $response->assertSessionHasErrors();

        // El movimiento no debe haberse eliminado
        $this->assertNull($movement->fresh()->deleted_at);
    }

    public function test_admin_can_delete_movement_not_in_cut_with_reason(): void
    {
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'expense',
            'amount' => 100.00,
            'concept' => 'Gasto de prueba',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('pos.cash.destroy', $movement), [
            'deletion_reason' => 'Error de captura',
        ]);

        $response->assertRedirect();

        $this->assertSoftDeleted($movement);
        $this->assertEquals($admin->id, $movement->fresh()->deleted_by);
        $this->assertEquals('Error de captura', $movement->fresh()->deletion_reason);
    }

    public function test_delete_requires_reason(): void
    {
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'expense',
            'amount' => 100.00,
            'concept' => 'Gasto de prueba',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('pos.cash.destroy', $movement), [
            'deletion_reason' => '',
        ]);

        $response->assertSessionHasErrors('deletion_reason');
    }

    public function test_employee_without_cash_manage_cannot_delete_movements(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell']); // sin pos.cash.manage
        $movement = CashMovement::create([
            'user_id' => $employee->id,
            'type' => 'in',
            'category' => 'expense',
            'amount' => 100.00,
            'concept' => 'Gasto de prueba',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($employee)->delete(route('pos.cash.destroy', $movement), [
            'deletion_reason' => 'Prueba',
        ]);

        $response->assertForbidden();
    }

    public function test_export_csv_returns_csv(): void
    {
        $admin = $this->admin();

        CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Dinero inicial',
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('pos.cash.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }
}
