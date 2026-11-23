<?php

namespace Tests\Feature;

use App\Models\DailyCut;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReopenCutVisibilityTest extends TestCase
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
        $user->givePermissionTo(['pos.access', 'pos.sell']);

        return $user;
    }

    private function closeOwnDay(User $user): void
    {
        DailyCut::create([
            'cut_key' => DailyCut::keyFor(today()->toDateString(), DailyCut::SCOPE_EMPLOYEE, $user->id),
            'cut_date' => today()->toDateString(),
            'scope' => DailyCut::SCOPE_EMPLOYEE,
            'user_id' => $user->id,
            'version' => 1,
            'totals' => [],
            'generated_by' => $user->id,
            'generated_at' => now(),
            'closed_at' => now(),
        ]);
    }

    public function test_reopen_button_is_hidden_while_the_own_day_is_open(): void
    {
        $response = $this->actingAs($this->employee())->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Reabrir mi corte del día')
            ->assertSee('#pos-reopen-day.hidden { display: none; }', false);

        $this->assertFalse($response->viewData('permissions')['own_day_closed']);
        $this->assertMatchesRegularExpression('/id="pos-reopen-day"[^>]*\bhidden"/', $response->getContent());
    }

    public function test_reopen_button_is_visible_when_the_own_day_was_closed(): void
    {
        $employee = $this->employee();
        $this->closeOwnDay($employee);

        $response = $this->actingAs($employee)->get(route('pos.index'))->assertOk();

        $this->assertTrue($response->viewData('permissions')['own_day_closed']);
        $this->assertDoesNotMatchRegularExpression('/id="pos-reopen-day"[^>]*\bhidden"/', $response->getContent());
    }

    public function test_another_employees_closed_cut_does_not_show_the_button(): void
    {
        $this->closeOwnDay($this->employee());

        $response = $this->actingAs($this->employee())->get(route('pos.index'))->assertOk();

        $this->assertFalse($response->viewData('permissions')['own_day_closed']);
        $this->assertMatchesRegularExpression('/id="pos-reopen-day"[^>]*\bhidden"/', $response->getContent());
    }
}
