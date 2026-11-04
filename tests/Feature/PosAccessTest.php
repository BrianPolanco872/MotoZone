<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_employee_can_access_pos_shell(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $response = $this->actingAs($employee)->get(route('pos.index'));

        $response->assertOk();
        $response->assertViewIs('pos.index');
        $response->assertSee('Punto de venta');
        $response->assertSee('La venta está vacía');
        $response->assertSee('Total');
        $response->assertSee('Cobrar');
        $response->assertDontSee('Devoluciones');
    }

    public function test_employee_with_sell_permission_can_charge(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell']);

        $response = $this->actingAs($employee)->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('Cobrar');
    }

    public function test_admin_can_access_pos_shell(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('pos.index'))->assertOk();
    }

    public function test_user_without_pos_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('pos.index'))->assertForbidden();
    }

    public function test_employee_cannot_access_admin_panel(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get('/admin')->assertForbidden();
    }

    public function test_pos_is_not_accessible_to_guests(): void
    {
        $this->get(route('pos.index'))->assertRedirect(route('login'));
    }
}
