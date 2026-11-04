<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_employee_role_has_no_base_permissions(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->assertTrue($employee->getAllPermissions()->isEmpty());
        $this->assertFalse($employee->can('pos.access'));
        $this->assertFalse($employee->can('admin.products.manage'));
    }

    public function test_admin_keeps_all_employee_permissions(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        foreach (User::assignablePermissions() as $permission) {
            $this->assertTrue($admin->can($permission), "El admin debería conservar {$permission}.");
        }

        $this->assertTrue($admin->can('pos.sell'));
        $this->assertTrue($admin->can('admin.users.manage'));
    }

    public function test_admin_can_assign_pos_permission_to_employee(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $response = $this->actingAs($this->admin())
            ->put(route('admin.users.permissions.update', $employee), [
                'permissions' => ['pos.access'],
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $employee->refresh();

        $this->assertTrue($employee->can('pos.access'));
        $this->assertFalse($employee->can('admin.products.manage'));

        $this->actingAs($employee)->get(route('pos.index'))->assertOk();
    }

    public function test_removing_permission_revokes_access(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $this->actingAs($this->admin())
            ->put(route('admin.users.permissions.update', $employee), ['permissions' => []])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($employee->fresh()->can('pos.access'));

        $this->actingAs($employee)->get(route('pos.index'))->assertForbidden();
    }

    public function test_employee_with_products_permission_can_manage_products_but_not_users(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('admin.products.manage');

        $this->actingAs($employee)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($employee)->get(route('admin.products.create'))->assertOk();
        $this->actingAs($employee)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_employee_without_products_permission_cannot_access_products(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_only_assignable_permissions_are_accepted(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $response = $this->actingAs($this->admin())
            ->from(route('admin.users.index'))
            ->put(route('admin.users.permissions.update', $employee), [
                'permissions' => ['admin.users.manage'],
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHasErrors('permissions.0');

        $this->assertFalse($employee->fresh()->can('admin.users.manage'));
    }

    public function test_permissions_cannot_be_assigned_to_an_admin(): void
    {
        $target = User::factory()->create()->assignRole('admin');

        $this->actingAs($this->admin())
            ->put(route('admin.users.permissions.update', $target), [
                'permissions' => ['pos.access'],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_delete_employee(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $employee))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $employee->id]);
        $this->assertFalse(User::query()->whereKey($employee->id)->exists());
        $this->assertTrue(User::withTrashed()->whereKey($employee->id)->exists());
    }

    public function test_admin_cannot_delete_an_administrator(): void
    {
        $target = User::factory()->create()->assignRole('admin');

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_employee_cannot_delete_other_employees(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $target = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
