<?php

namespace Tests\Feature;

use App\Models\DailyCut;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class PosBitacoraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_with_cut_permission_can_open_the_read_only_bitacora(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->get(route('pos.bitacora.index'));

        $response->assertOk();
        $response->assertSee('Bitácora');
        $response->assertSee('Descargar PDF consolidado');
        $response->assertDontSee('Eliminar seleccionados');
    }

    public function test_user_without_cut_permission_cannot_open_bitacora_or_pdf(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $this->actingAs($employee)
            ->get(route('pos.bitacora.index'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('pos.bitacora.pdf'))
            ->assertForbidden();

        $managerWithoutPosAccess = User::factory()->create()->assignRole('empleado');
        $managerWithoutPosAccess->givePermissionTo('pos.cut.manage');

        $this->actingAs($managerWithoutPosAccess)
            ->get(route('pos.bitacora.index'))
            ->assertForbidden();
    }

    public function test_date_and_employee_filters_return_only_the_matching_cut_scope(): void
    {
        $manager = $this->manager();
        $employee = User::factory()->create(['name' => 'Empleado bitácora'])->assignRole('empleado');
        $date = '2026-10-12';
        $globalCut = $this->createCut($manager, $date, DailyCut::SCOPE_GLOBAL, null, 1, 900);
        $employeeCut = $this->createCut($manager, $date, DailyCut::SCOPE_EMPLOYEE, $employee, 1, 300);
        $this->createCut($manager, '2026-10-11', DailyCut::SCOPE_GLOBAL, null, 1, 700);
        $employee->delete();
        $employee->syncRoles([]);

        $globalResponse = $this->actingAs($manager)->get(route('pos.bitacora.index', ['date' => $date]));
        $employeeResponse = $this->actingAs($manager)->get(route('pos.bitacora.index', [
            'date' => $date,
            'employee' => $employee->id,
        ]));

        $this->assertSame([$globalCut->id], $globalResponse->viewData('cuts')->getCollection()->modelKeys());
        $globalResponse->assertSee('Todos (global)');
        $this->assertSame([$employeeCut->id], $employeeResponse->viewData('cuts')->getCollection()->modelKeys());
        $employeeResponse->assertSee('Empleado bitácora');
    }

    public function test_bitacora_paginates_eight_cut_versions_and_keeps_the_date_filter(): void
    {
        $manager = $this->manager();
        $date = '2026-10-13';

        for ($version = 1; $version <= 9; $version++) {
            $this->createCut($manager, $date, DailyCut::SCOPE_GLOBAL, null, $version, $version * 100);
        }

        $firstPage = $this->actingAs($manager)->get(route('pos.bitacora.index', ['date' => $date]));
        $secondPage = $this->actingAs($manager)->get(route('pos.bitacora.index', ['date' => $date, 'page' => 2]));

        $this->assertSame(8, $firstPage->viewData('cuts')->count());
        $this->assertSame(9, $firstPage->viewData('cuts')->total());
        $this->assertSame(1, $secondPage->viewData('cuts')->count());
        $secondPage->assertSee('value="'.$date.'"', false);
    }

    public function test_pdf_exports_all_filtered_cuts_not_only_the_current_web_page(): void
    {
        $manager = $this->manager();
        $date = '2026-10-14';
        $expectedIds = [];

        for ($version = 1; $version <= 9; $version++) {
            $expectedIds[] = $this->createCut(
                $manager,
                $date,
                DailyCut::SCOPE_GLOBAL,
                null,
                $version,
                $version * 100,
            )->id;
        }

        $pdf = Mockery::mock();
        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use ($expectedIds): bool {
                return $view === 'pos.bitacora.pdf'
                    && $data['cuts']->modelKeys() === array_reverse($expectedIds);
            })
            ->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('letter', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')
            ->once()
            ->with("bitacora-cortes-{$date}.pdf")
            ->andReturn(response('pdf', 200, ['Content-Type' => 'application/pdf']));

        $response = $this->actingAs($manager)->get(route('pos.bitacora.pdf', ['date' => $date]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(9, DailyCut::query()->count());
    }

    private function manager(): User
    {
        $manager = User::factory()->create()->assignRole('empleado');
        $manager->givePermissionTo(['pos.access', 'pos.cut.manage']);

        return $manager;
    }

    private function createCut(
        User $generatedBy,
        string $date,
        string $scope,
        ?User $employee,
        int $version,
        float $sales,
    ): DailyCut {
        $difference = 0;

        return DailyCut::create([
            'cut_key' => DailyCut::keyFor($date, $scope, $employee?->id),
            'cut_date' => $date,
            'scope' => $scope,
            'user_id' => $employee?->id,
            'version' => $version,
            'totals' => [
                'totals' => ['sales' => $sales],
                'cash_box_panel' => ['subtotal' => $sales],
                'reconciliation' => ['difference' => $difference],
            ],
            'generated_by' => $generatedBy->id,
            'generated_at' => Carbon::parse($date)->setTime(12, 0),
            'difference' => $difference,
            'is_reconciled' => true,
        ]);
    }
}
