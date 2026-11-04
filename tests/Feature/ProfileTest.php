<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_update_own_name_and_email(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $response = $this->actingAs($employee)->put(route('admin.profile.update'), [
            'name' => 'Nuevo Nombre',
            'email' => 'nuevo@motozone.test',
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));

        $employee->refresh();

        $this->assertSame('Nuevo Nombre', $employee->name);
        $this->assertSame('nuevo@motozone.test', $employee->email);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password'])->assignRole('empleado');

        $response = $this->actingAs($user)
            ->from(route('admin.profile.edit'))
            ->put(route('admin.profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHasErrors('current_password');

        $this->assertFalse(Hash::check('nueva12345', $user->fresh()->password));
    }

    public function test_password_change_works_with_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password'])->assignRole('empleado');

        $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'password',
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])->assertRedirect(route('admin.profile.edit'));

        $this->assertTrue(Hash::check('nueva12345', $user->fresh()->password));
    }

    public function test_admin_can_update_employee_data(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($this->admin())->put(route('admin.profile.employees.update', $employee), [
            'name' => 'Empleado Corregido',
            'email' => 'corregido@motozone.test',
            'password' => 'otra12345',
            'password_confirmation' => 'otra12345',
        ])->assertRedirect(route('admin.profile.edit'));

        $employee->refresh();

        $this->assertSame('Empleado Corregido', $employee->name);
        $this->assertSame('corregido@motozone.test', $employee->email);
        $this->assertTrue(Hash::check('otra12345', $employee->password));
    }

    public function test_admin_cannot_update_another_admin_through_profile(): void
    {
        $target = User::factory()->create()->assignRole('admin');

        $this->actingAs($this->admin())
            ->put(route('admin.profile.employees.update', $target), [
                'name' => 'Hack',
                'email' => 'hack@motozone.test',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_update_other_users_through_profile(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $target = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)
            ->put(route('admin.profile.employees.update', $target), [
                'name' => 'Hack',
                'email' => 'hack@motozone.test',
            ])
            ->assertForbidden();
    }

    public function test_employee_sees_own_profile_without_employee_block(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Mis datos')
            ->assertDontSee('Datos de empleados');
    }

    public function test_admin_sees_employee_management_block(): void
    {
        $employee = User::factory()->create(['name' => 'Empleado Visible'])->assignRole('empleado');

        $this->actingAs($this->admin())->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Datos de empleados')
            ->assertSee('Empleado Visible');
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
