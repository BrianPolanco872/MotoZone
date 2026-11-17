<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
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

    public function test_concept_is_required_for_cash_movement(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->post(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 100.00,
            'concept' => 'Movimiento de prueba',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('cash_movements', [
            'user_id' => $employee->id,
            'category' => 'initial',
            'amount' => 100.00,
            'concept' => 'Movimiento de prueba',
        ]);
    }

    public function test_concept_is_required_for_cash_movement_validation(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->post(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 100.00,
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
            'concept' => 'Concepto no visible en la vista principal',
            'happened_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('pos.cash.archive', $movement), ['confirm' => true]);

        $response = $this->actingAs($admin)->get(route('pos.cash.index'));

        $response->assertOk();
        // El movimiento archivado no debe aparecer en la vista principal
        $response->assertDontSee('Concepto no visible en la vista principal');
        $response->assertSee('0 movimientos');
    }

    public function test_archived_movement_in_archived_view(): void
    {
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 500.00,
            'concept' => 'Concepto archivado visible',
            'happened_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('pos.cash.archive', $movement), ['confirm' => true]);

        $response = $this->actingAs($admin)->get(route('pos.cash.index', ['show_archived' => true]));

        $response->assertOk();
        $response->assertSee('Concepto archivado visible');
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

    #[TestWith(['pos.cash.destroy'])]
    #[TestWith(['admin.caja.destroy'])]
    public function test_cannot_delete_movement_in_closed_cut(string $route): void
    {
        $this->freezeTime();
        $admin = $this->admin();

        // La venta se pagó ayer, pero el movimiento pertenece al cierre de hoy.
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
            'paid_at' => now()->subDay(),
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
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route($route, $movement), [
            'deletion_reason' => 'Prueba de eliminación',
        ]);

        $response->assertSessionHasErrors();

        // El movimiento no debe haberse eliminado
        $this->assertNull($movement->fresh()->deleted_at);
    }

    #[TestWith(['pos.cash.destroy'])]
    #[TestWith(['admin.caja.destroy'])]
    public function test_manual_movement_cannot_be_deleted_after_closing_and_restoring(string $route): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $date = today('America/Mexico_City')->toDateString();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'out',
            'category' => 'expense',
            'amount' => 100,
            'concept' => 'Gasto manual del día cerrado',
            'happened_at' => $date.' 00:00:00',
        ]);

        DailyCut::create([
            'cut_key' => DailyCut::keyFor($date, DailyCut::SCOPE_GLOBAL),
            'cut_date' => $date,
            'scope' => DailyCut::SCOPE_GLOBAL,
            'version' => 1,
            'totals' => [],
            'generated_by' => $admin->id,
            'generated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('pos.cuts.close-day', ['date' => $date]), ['force' => true])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertNotNull($movement->fresh()->archivado_at);

        $this->post(route('pos.cash.restore', $movement))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertNull($movement->fresh()->archivado_at);

        $this->delete(route($route, $movement), ['deletion_reason' => 'No debe cambiar el cierre'])
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertNotSoftDeleted($movement);
        $this->assertNull($movement->fresh()->deleted_by);
        $this->assertNull($movement->fresh()->deletion_reason);
    }

    #[TestWith(['pos.cash.destroy', false])]
    #[TestWith(['admin.caja.destroy', false])]
    #[TestWith(['pos.cash.destroy', true])]
    #[TestWith(['admin.caja.destroy', true])]
    public function test_movement_can_be_deleted_without_effective_closure_of_its_day(string $route, bool $closedPreviousDay): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $movement = CashMovement::create([
            'user_id' => $admin->id,
            'type' => 'out',
            'category' => 'expense',
            'amount' => 100,
            'concept' => 'Gasto en día abierto',
            'happened_at' => now()->endOfDay(),
        ]);
        $date = $closedPreviousDay ? now()->subDay()->toDateString() : now()->toDateString();

        DailyCut::create([
            'cut_key' => DailyCut::keyFor($date, DailyCut::SCOPE_GLOBAL),
            'cut_date' => $date,
            'scope' => DailyCut::SCOPE_GLOBAL,
            'version' => 1,
            'totals' => [],
            'generated_by' => $admin->id,
            'generated_at' => now(),
            'closed_at' => $closedPreviousDay ? now() : null,
        ]);

        $this->actingAs($admin)
            ->delete(route($route, $movement), ['deletion_reason' => 'Error de captura'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($movement);
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
