<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\DailyCutService;
use App\Services\ReturnService;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
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
        $email = $employee->email;

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $employee))
            ->assertRedirect(route('admin.users.index'));

        // El empleado se borra físicamente (forceDelete), por lo que
        // ya no existe ni siquiera como soft-deleted y su correo queda libre.
        $this->assertFalse(User::query()->whereKey($employee->id)->exists());
        $this->assertFalse(User::withTrashed()->whereKey($employee->id)->exists());
        $this->assertFalse(User::query()->where('email', $email)->exists());
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

    public function test_admin_can_delete_employee_with_own_history(): void
    {
        $this->freezeTime();
        $employee = User::factory()->create()->assignRole('empleado');
        $sale = $this->employeeHistory($employee);

        $this->actingAs($this->admin())->delete(route('admin.users.destroy', $employee))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        foreach (['cash_movements', 'stock_movements', 'daily_cuts', 'customer_payments', 'supplier_payments', 'audit_logs'] as $table) {
            $this->assertDatabaseMissing($table, ['user_id' => $employee->id]);
        }
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
        $this->assertDatabaseMissing('sale_returns', ['sale_id' => $sale->id]);
        $this->assertDatabaseCount('sale_return_items', 0);
        $this->assertDatabaseCount('products', 1);
    }

    #[TestWith(['pending'])]
    #[TestWith(['approved'])]
    #[TestWith(['rejected'])]
    public function test_employee_deletion_is_rejected_without_changes_when_they_requested_a_return_for_another_seller(string $status): void
    {
        $this->freezeTime();
        $employee = User::factory()->create()->assignRole('empleado');
        $this->employeeHistory($employee);
        $otherEmployee = User::factory()->create()->assignRole('empleado');
        $otherSale = $this->employeeHistory($otherEmployee);
        SaleReturn::create([
            'sale_id' => $otherSale->id,
            'requested_by' => $employee->id,
            'status' => $status,
            'reason' => 'Devolución de una venta ajena',
            'total' => 100,
        ]);
        $this->actingAs($this->admin());
        $historyBefore = $this->historySnapshot();

        $this->from(route('admin.users.index'))->delete(route('admin.users.destroy', $employee))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors([
                'user' => 'No se puede eliminar este empleado porque solicitó devoluciones de ventas de otro usuario.',
            ]);

        $this->assertSame($historyBefore, $this->historySnapshot());
    }

    public function test_employee_deletion_rolls_back_all_history_when_the_final_delete_fails(): void
    {
        $this->freezeTime();
        $employee = User::factory()->create()->assignRole('empleado');
        $this->employeeHistory($employee);
        $this->actingAs($this->admin());
        $historyBefore = $this->historySnapshot();
        Event::listen('eloquent.forceDeleting: '.User::class, function (): void {
            throw new RuntimeException('Simulated final employee deletion failure');
        });

        $this->delete(route('admin.users.destroy', $employee))->assertServerError();

        $this->assertSame($historyBefore, $this->historySnapshot());
    }

    public function test_employee_deletion_rolls_back_history_when_the_final_delete_is_cancelled(): void
    {
        $this->freezeTime();
        $employee = User::factory()->create()->assignRole('empleado');
        $this->employeeHistory($employee);
        $this->actingAs($this->admin());
        $historyBefore = $this->historySnapshot();
        Event::listen('eloquent.forceDeleting: '.User::class, fn (): bool => false);

        $this->from(route('admin.users.index'))->delete(route('admin.users.destroy', $employee))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');

        $this->assertSame($historyBefore, $this->historySnapshot());
    }

    private function employeeHistory(User $employee): Sale
    {
        $product = Product::factory()->create(['stock' => 5, 'price' => 100]);
        $sale = app(SaleService::class)->create($employee, [
            'idempotency_key' => 'employee-history-'.$employee->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        app(ReturnService::class)->request($employee, [
            'sale_id' => $sale->id,
            'reason' => 'Devolución propia',
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]],
        ]);
        CustomerPayment::create([
            'user_id' => $employee->id, 'amount' => 20, 'method' => 'cash',
            'customer_name' => 'Cliente de prueba', 'happened_at' => now(),
        ]);
        SupplierPayment::create([
            'user_id' => $employee->id, 'amount' => 10, 'method' => 'cash',
            'supplier_name' => 'Proveedor de prueba', 'reason' => 'Pago de prueba', 'happened_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $employee->id, 'action' => AuditLog::ACTION_MAKE_CUT, 'entity_type' => 'daily_cut',
        ]);
        app(DailyCutService::class)->generate(today(), $employee, $employee);

        return $sale;
    }

    /**
     * @return array<string, string>
     */
    private function historySnapshot(): array
    {
        $snapshot = [];
        foreach (['users', 'products', 'sales', 'sale_items', 'sale_returns', 'sale_return_items', 'cash_movements', 'stock_movements', 'daily_cuts', 'customer_payments', 'supplier_payments', 'audit_logs', 'model_has_roles', 'model_has_permissions'] as $table) {
            $snapshot[$table] = DB::table($table)->get()->toJson();
        }

        return $snapshot;
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
