<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PosCutPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_uses_the_selected_saved_breakdown_after_live_data_changes(): void
    {
        $manager = $this->manager();
        $employee = User::factory()->create(['name' => 'Nombre actual']);
        $totals = $this->totals();
        $totals['employee_breakdown'] = $this->breakdown($employee);
        $cut = $this->cut($manager, $totals);
        $this->cut($manager, $this->totals(9876), version: 2);
        $this->addLiveCash($employee);

        $response = $this->actingAs($manager)->get(route('pos.cuts.print', [
            'date' => '2026-10-12', 'version' => 1,
        ]));

        $response->assertOk()
            ->assertSee('Guardado <script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Nombre actual')
            ->assertSee('$1,250.00')
            ->assertSee('$425.00')
            ->assertSee('$-25.00')
            ->assertSee('Versión 1')
            ->assertSee('Generado 12/10/2026 12:30');
        $this->assertSame($cut->id, $response->viewData('cut')->id);
    }

    public function test_pdf_by_id_renders_the_saved_breakdown_with_real_dompdf(): void
    {
        $manager = $this->manager();
        $employee = User::factory()->create(['name' => 'Nombre actual']);
        $totals = $this->totals();
        $breakdown = $this->breakdown($employee);
        $breakdown['employees'][0]['name'] = 'Nombre guardado';
        $totals['employee_breakdown'] = $breakdown;
        $cut = $this->cut($manager, $totals);
        $this->cut($manager, $this->totals(9876), version: 2);
        $this->addLiveCash($employee);

        $response = $this->actingAs($manager)->get(route('pos.cuts.pdf', ['id' => $cut->id]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $text = $this->pdfStreams($response->getContent());
        $this->assertStringContainsString('Nombre guardado', $text);
        $this->assertStringNotContainsString('Nombre actual', $text);
        $this->assertStringContainsString('$1,250.00', $text);
        $this->assertStringContainsString('$425.00', $text);
        $this->assertStringNotContainsString('$1,300.00', $text);
        $this->assertStringContainsString('12/10/2026 12:30', $text);
    }

    #[DataProvider('exportRoutes')]
    public function test_legacy_snapshots_keep_real_totals_and_warn_without_rebuilding_employees(string $route): void
    {
        $manager = $this->manager();
        $employee = User::factory()->create(['name' => 'Empleado posterior'])->assignRole('empleado');
        $this->addLiveCash($employee);
        $this->cut($manager, $this->totals());

        $response = $this->actingAs($manager)->get(route($route, ['date' => '2026-10-12']));

        $response->assertOk();
        $content = $route === 'pos.cuts.pdf' ? $this->pdfStreams($response->getContent()) : $response->getContent();
        $this->assertStringContainsString('$1,250.00', $content);
        $this->assertStringContainsString('$425.00', $content);
        $this->assertStringContainsString('Desglose por empleado no disponible', $content);
        $this->assertStringNotContainsString('Empleado posterior', $content);
    }

    public function test_employee_pdf_version_uses_own_scope_instead_of_global_or_requested_other_employee(): void
    {
        $employee = $this->manager(canSeeAny: false);
        $other = User::factory()->create();
        $this->cut($employee, $this->totals(9876));
        $this->cut($employee, $this->totals(8765), $other);
        $this->cut($employee, $this->totals(), $employee);
        $this->cut($employee, $this->totals(7654), $employee, 2);

        $response = $this->actingAs($employee)->get(route('pos.cuts.pdf', [
            'date' => '2026-10-12', 'version' => 1, 'employee' => $other->id, 'preview' => 1,
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline;', $response->headers->get('Content-Disposition'));
        $text = $this->pdfStreams($response->getContent());
        $this->assertStringContainsString('$1,250.00', $text);
        $this->assertStringContainsString('$425.00', $text);
        $this->assertStringNotContainsString('$9,876.00', $text);
        $this->assertStringNotContainsString('Desglose por empleado no disponible', $text);
    }

    public function test_employee_can_download_own_snapshot_by_id_without_a_breakdown(): void
    {
        $employee = $this->manager(canSeeAny: false);
        $cut = $this->cut($employee, $this->totals(), $employee);

        $response = $this->actingAs($employee)->get(route('pos.cuts.pdf', ['id' => $cut->id]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('$1,250.00', $this->pdfStreams($response->getContent()));
    }

    #[DataProvider('foreignScopes')]
    public function test_employee_cannot_download_global_or_other_employee_snapshot_by_id(bool $global): void
    {
        $employee = $this->manager(canSeeAny: false);
        $other = User::factory()->create();
        $cut = $this->cut($employee, $this->totals(), $global ? null : $other);

        $this->actingAs($employee)->getJson(route('pos.cuts.pdf', ['id' => $cut->id]))->assertNotFound();
    }

    #[DataProvider('exportRoutes')]
    public function test_missing_saved_version_returns_404_instead_of_live_totals(string $route): void
    {
        $manager = $this->manager();
        $this->cut($manager, $this->totals());

        $this->actingAs($manager)->getJson(route($route, [
            'date' => '2026-10-12', 'version' => 99,
        ]))->assertNotFound();
    }

    public function test_pdf_rejects_invalid_version_as_validation_error_not_500(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->getJson(route('pos.cuts.pdf', ['version' => 0]))
            ->assertUnprocessable()->assertJsonValidationErrors('version');
    }

    public function test_print_shows_zero_physical_count_and_negative_shortage(): void
    {
        $manager = $this->manager();
        $cut = $this->cut($manager, $this->totals());
        $cut->update(['cash_counted' => 0, 'difference' => -425, 'cash_counted_at' => '2026-10-12 13:00:00']);

        $this->actingAs($manager)->get(route('pos.cuts.print', ['date' => '2026-10-12']))
            ->assertOk()->assertSee('Efectivo contado físicamente')->assertSee('−$425.00');
    }

    public function test_manager_can_export_a_deleted_employees_saved_version(): void
    {
        $manager = $this->manager();
        $employee = User::factory()->create(['name' => 'Empleado eliminado']);
        $this->cut($manager, $this->totals(9876));
        $this->cut($manager, $this->totals(), $employee);
        $employee->delete();

        $response = $this->actingAs($manager)->get(route('pos.cuts.pdf', [
            'date' => '2026-10-12', 'version' => 1, 'employee' => $employee->id,
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $text = $this->pdfStreams($response->getContent());
        $this->assertStringContainsString('Empleado eliminado', $text);
        $this->assertStringContainsString('$1,250.00', $text);
        $this->assertStringNotContainsString('$9,876.00', $text);
    }

    public function test_print_uses_persisted_should_have_without_recalculating_sales(): void
    {
        $manager = $this->manager();
        $totals = $this->totals();
        unset($totals['cash_box_panel']['subtotal']);
        $totals['should_have'] = 321;
        $this->cut($manager, $totals);

        $this->actingAs($manager)->get(route('pos.cuts.print', ['date' => '2026-10-12']))
            ->assertOk()->assertSeeInOrder(['Subtotal en caja', '$321.00']);
    }

    #[DataProvider('exportRoutes')]
    public function test_unallocated_mixed_payments_show_the_saved_warning_even_with_zero_difference(string $route): void
    {
        $manager = $this->manager();
        $totals = $this->totals();
        $totals['sales'] = ['total' => 1250, 'total_cash' => 0, 'total_non_cash' => 0, 'total_mixed' => 1250];
        $warning = 'Hay pagos mixtos sin desglose de efectivo. El efectivo de esos pagos no está determinado.';
        $totals['reconciliation'] = [
            'computer_subtotal' => 425, 'cash_box_subtotal' => 425, 'difference' => 0,
            'is_reconciled' => false, 'has_unallocated_mixed' => true, 'warning' => $warning,
        ];
        $this->cut($manager, $totals);

        $response = $this->actingAs($manager)->get(route($route, ['date' => '2026-10-12']));

        $response->assertOk();
        $content = $route === 'pos.cuts.pdf' ? $this->pdfStreams($response->getContent()) : $response->getContent();
        $this->assertStringContainsString('Hay pagos mixtos sin desglose de efectivo.', $content);
        $this->assertStringContainsString('Pagos mixtos sin desglose', $content);
        $this->assertStringContainsString('$1,250.00', $content);
        $this->assertStringNotContainsString('>CUADRA<', $content);
    }

    public static function exportRoutes(): array
    {
        return ['print' => ['pos.cuts.print'], 'pdf' => ['pos.cuts.pdf']];
    }

    public static function foreignScopes(): array
    {
        return ['global' => [true], 'other employee' => [false]];
    }

    private function manager(bool $canSeeAny = true): User
    {
        $this->seed(RolePermissionSeeder::class);
        $manager = User::factory()->create()->assignRole('empleado');
        $manager->givePermissionTo(['pos.access', 'pos.cut.manage']);
        if ($canSeeAny) {
            $manager->givePermissionTo('pos.sales.day.any');
        }

        return $manager;
    }

    private function cut(User $manager, array $totals, ?User $employee = null, int $version = 1): DailyCut
    {
        $scope = $employee ? DailyCut::SCOPE_EMPLOYEE : DailyCut::SCOPE_GLOBAL;

        return DailyCut::create([
            'cut_key' => DailyCut::keyFor('2026-10-12', $scope, $employee?->id),
            'cut_date' => '2026-10-12',
            'scope' => $scope,
            'user_id' => $employee?->id,
            'version' => $version,
            'totals' => $totals,
            'generated_by' => $manager->id,
            'generated_at' => Carbon::parse('2026-10-12 12:30:00'),
        ]);
    }

    private function totals(float $sales = 1250): array
    {
        return [
            'sales' => ['total' => $sales, 'total_cash' => 250, 'total_non_cash' => 1000],
            'totals' => ['sales' => $sales, 'tickets' => 2],
            'computer_panel' => ['subtotal' => 450],
            'cash_box_panel' => [
                'cash_in_total' => 50, 'sales_movements' => 225, 'customer_payments' => 200,
                'refunds' => 10, 'cash_out' => 40, 'subtotal' => 425,
            ],
            'reconciliation' => [
                'computer_subtotal' => 450, 'cash_box_subtotal' => 425,
                'difference' => -25, 'is_reconciled' => false,
            ],
        ];
    }

    private function breakdown(User $employee): array
    {
        $row = [
            'id' => $employee->id, 'name' => 'Guardado <script>alert(1)</script>', 'email' => $employee->email,
            'status' => 'closed', 'is_day_closed' => true, 'closed_at' => '2026-10-12 12:00:00',
            'tickets' => 2, 'sales' => 1250, 'cash' => 225, 'other_payments' => 1000,
            'refunds' => 10, 'cash_in' => 50, 'cash_out' => 40, 'customer_payments' => 200,
            'should_have' => 425, 'difference' => -25,
        ];

        return ['employees' => [$row], 'total' => $row];
    }

    private function addLiveCash(User $employee): void
    {
        CashMovement::create([
            'user_id' => $employee->id, 'type' => 'in', 'category' => 'other', 'amount' => 9999,
            'concept' => 'Movimiento posterior al corte', 'happened_at' => '2026-10-12 18:00:00',
        ]);
    }

    private function pdfStreams(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);

        return implode('\n', array_map(static fn (string $stream): string => @gzuncompress($stream) ?: $stream, $matches[1]));
    }
}
