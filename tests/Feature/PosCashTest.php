<?php

namespace Tests\Feature;

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

    /** @test */
    public function employee_can_register_cash_movement(): void
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

    /** @test */
    public function cash_movement_requires_valid_category(): void
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

    /** @test */
    public function cash_movement_requires_positive_amount(): void
    {
        $employee = $this->employee();

        $response = $this->actingAs($employee)->postJson(route('pos.cash.store'), [
            'category' => 'initial',
            'amount' => 0,
            'concept' => 'Prueba',
        ]);

        $response->assertUnprocessable();
    }
}
