<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_create_user_and_assign_role(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Empleado de mostrador',
            'email' => 'mostrador@motozone.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => '1',
            'role' => 'empleado',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'mostrador@motozone.test')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('empleado'));
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Empleado original',
            'email' => 'original@motozone.test',
            'password' => 'password123',
        ])->assignRole('empleado');

        $originalPassword = $user->password;

        $response = $this->actingAs($this->admin())->put(route('admin.users.update', $user), [
            'name' => 'Empleado actualizado',
            'email' => 'actualizado@motozone.test',
            'password' => '',
            'password_confirmation' => '',
            'is_active' => '1',
            'role' => 'empleado',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('Empleado actualizado', $user->name);
        $this->assertSame('actualizado@motozone.test', $user->email);
        $this->assertSame($originalPassword, $user->password);
        $this->assertTrue($user->hasRole('empleado'));
    }

    public function test_admin_cannot_deactivate_self_or_remove_own_admin_role(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'empleado',
            ]);

        $response->assertRedirect(route('admin.users.edit', $admin));
        $response->assertSessionHasErrors(['is_active', 'role']);

        $admin->refresh();

        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_admin_can_render_user_management_forms(): void
    {
        $admin = $this->admin();
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($employee->name)
            ->assertSee('Agregar empleado')
            ->assertSee('Equipo y permisos')
            ->assertSee('Usar el punto de venta')
            ->assertSee('Ver y editar productos')
            ->assertSee('Abrir la caja para cobrar y registrar ventas.')
            ->assertSee('Eliminar');

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Nuevo usuario');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $employee))
            ->assertOk()
            ->assertSee('Editar usuario')
            ->assertSee($employee->email);
    }

    public function test_employee_cannot_manage_users(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get(route('admin.users.index'))->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
