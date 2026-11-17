<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosCashHistoryFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create()->assignRole('admin');
        $user->givePermissionTo(['pos.access', 'pos.cash.manage', 'pos.sales.day.any', 'pos.cut.manage']);

        return $user;
    }

    private function employee(): User
    {
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo(['pos.access', 'pos.caja.view', 'pos.cash.manage']);

        return $user;
    }

    private function movement(User $user, string $concept, Carbon $happenedAt, ?Carbon $archivedAt = null): CashMovement
    {
        return CashMovement::create([
            'user_id' => $user->id,
            'type' => 'in',
            'category' => 'initial',
            'amount' => 100.00,
            'concept' => $concept,
            'happened_at' => $happenedAt,
            'archivado_at' => $archivedAt,
            'archivado_por' => $archivedAt !== null ? $user->id : null,
        ]);
    }

    public function test_closing_the_day_archives_the_day_movements(): void
    {
        $admin = $this->admin();
        $today = today('America/Mexico_City')->toDateString();

        DailyCut::query()->create([
            'cut_key' => DailyCut::keyFor($today, DailyCut::SCOPE_GLOBAL),
            'cut_date' => $today,
            'scope' => DailyCut::SCOPE_GLOBAL,
            'user_id' => null,
            'version' => 1,
            'totals' => [],
            'generated_by' => $admin->id,
            'generated_at' => now(),
        ]);

        $movement = $this->movement($admin, 'Movimiento que debe archivarse al cerrar el día', now());

        $this->actingAs($admin)
            ->post(route('pos.cuts.close-day', ['date' => $today]), ['force' => true])
            ->assertRedirect();

        $this->assertNotNull($movement->fresh()->archivado_at);

        $this->actingAs($admin)
            ->get(route('pos.cash.index'))
            ->assertOk()
            ->assertDontSee('Movimiento que debe archivarse al cerrar el día');

        $this->actingAs($admin)
            ->get(route('pos.cash.index', ['show_archived' => 1]))
            ->assertOk()
            ->assertSee('Movimiento que debe archivarse al cerrar el día');
    }

    public function test_archived_view_shows_only_archived_movements(): void
    {
        $admin = $this->admin();

        $this->movement($admin, 'Movimiento activo visible', now());
        $this->movement($admin, 'Movimiento archivado visible', now(), now());

        $this->actingAs($admin)
            ->get(route('pos.cash.index', ['show_archived' => 1]))
            ->assertOk()
            ->assertSee('Movimiento archivado visible')
            ->assertDontSee('Movimiento activo visible');
    }

    public function test_history_only_shows_current_day_by_default(): void
    {
        $admin = $this->admin();

        $this->movement($admin, 'Movimiento de ayer sin archivar', now()->subDay()->setTime(12, 0));
        $this->movement($admin, 'Movimiento de ayer archivado', now()->subDay()->setTime(13, 0), now());
        $this->movement($admin, 'Movimiento de hoy', now());

        $this->actingAs($admin)
            ->get(route('pos.cash.index'))
            ->assertOk()
            ->assertSee('Movimiento de hoy')
            ->assertDontSee('Movimiento de ayer sin archivar')
            ->assertDontSee('Movimiento de ayer archivado');

        $this->actingAs($admin)
            ->get(route('pos.cash.index', ['show_archived' => 1]))
            ->assertOk()
            ->assertSee('Movimiento de ayer archivado')
            ->assertDontSee('Movimiento de ayer sin archivar')
            ->assertDontSee('Movimiento de hoy');
    }

    public function test_archived_history_can_be_filtered_by_day(): void
    {
        $admin = $this->admin();

        $this->movement($admin, 'Movimiento del día quince de enero', Carbon::parse('2026-01-15 10:00:00'), now());
        $this->movement($admin, 'Movimiento del mismo mes, otro día', Carbon::parse('2026-01-20 10:00:00'), now());
        $this->movement($admin, 'Movimiento de febrero', Carbon::parse('2026-02-10 10:00:00'), now());

        $this->actingAs($admin)
            ->get(route('pos.cash.index', ['show_archived' => 1, 'day' => '2026-01-15']))
            ->assertOk()
            ->assertSee('Movimiento del día quince de enero')
            ->assertDontSee('Movimiento del mismo mes, otro día')
            ->assertDontSee('Movimiento de febrero');
    }

    public function test_admin_can_filter_history_by_employee(): void
    {
        $admin = $this->admin();
        $firstEmployee = $this->employee();
        $secondEmployee = $this->employee();

        $this->movement($firstEmployee, 'Movimiento del primer empleado', now());
        $this->movement($secondEmployee, 'Movimiento del segundo empleado', now());

        $this->actingAs($admin)
            ->get(route('pos.cash.index', ['employee' => $firstEmployee->id]))
            ->assertOk()
            ->assertSee('Movimiento del primer empleado')
            ->assertDontSee('Movimiento del segundo empleado');
    }

    public function test_employee_cannot_see_movements_of_another_employee(): void
    {
        $firstEmployee = $this->employee();
        $secondEmployee = $this->employee();

        $this->movement($firstEmployee, 'Movimiento propio visible', now());
        $this->movement($secondEmployee, 'Movimiento ajeno oculto', now());

        $this->actingAs($firstEmployee)
            ->get(route('pos.cash.index', ['employee' => $secondEmployee->id]))
            ->assertOk()
            ->assertDontSee('Movimiento propio visible')
            ->assertDontSee('Movimiento ajeno oculto');
    }

    #[TestWith(['pos.cash.export'])]
    #[TestWith(['admin.caja.export'])]
    public function test_employee_exports_only_own_movements(string $route): void
    {
        $this->freezeTime();
        $employee = $this->employee();
        $otherEmployee = $this->employee();
        $this->movement($employee, 'Movimiento propio exportable', now());
        $this->movement($otherEmployee, 'Movimiento ajeno protegido', now());

        $response = $this->actingAs($employee)->get(route($route));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Movimiento propio exportable', $content);
        $this->assertStringNotContainsString('Movimiento ajeno protegido', $content);
    }

    #[TestWith(['pos.cash.export'])]
    #[TestWith(['admin.caja.export'])]
    public function test_employee_filter_cannot_export_another_employees_archived_movements(string $route): void
    {
        $this->freezeTime();
        $employee = $this->employee();
        $otherEmployee = $this->employee();
        $this->movement($employee, 'Archivado propio exportable', now(), now());
        $this->movement($otherEmployee, 'Archivado ajeno protegido', now(), now());

        $response = $this->actingAs($employee)->get(route($route, [
            'employee' => $otherEmployee->id,
            'show_archived' => 1,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringNotContainsString('Archivado propio exportable', $content);
        $this->assertStringNotContainsString('Archivado ajeno protegido', $content);
    }

    #[TestWith(['pos.cash.export'])]
    #[TestWith(['admin.caja.export'])]
    public function test_export_with_any_employee_permission_preserves_filters(string $route): void
    {
        $this->freezeTime();
        $employee = $this->employee()->givePermissionTo('pos.sales.day.any');
        $otherEmployee = $this->employee();
        $this->movement($employee, 'Archivo propio no solicitado', now(), now());
        $this->movement($otherEmployee, 'Archivo ajeno solicitado', now(), now());
        $this->movement($otherEmployee, 'Archivo ajeno fuera de fecha', now()->subDay(), now());
        $this->movement($otherEmployee, 'Archivo ajeno sin coincidencia', now(), now());

        $response = $this->actingAs($employee)->get(route($route, [
            'employee' => $otherEmployee->id,
            'show_archived' => 1,
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'type' => 'in',
            'search' => 'solicitado',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Archivo ajeno solicitado', $content);
        $this->assertStringNotContainsString('Archivo propio no solicitado', $content);
        $this->assertStringNotContainsString('Archivo ajeno fuera de fecha', $content);
        $this->assertStringNotContainsString('Archivo ajeno sin coincidencia', $content);
    }
}
