<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateEmployeePermissionsRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('roles', 'permissions')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', [
            'users' => $users,
            'permissionCatalog' => User::PERMISSION_CATALOG,
            'permissionLabels' => User::permissionLabels(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => $this->roles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = $data['role'];

        unset($data['role']);

        $user = User::create($data);
        $user->syncRoles([$role]);

        // Los empleados reciben permisos individuales por módulo (punto de venta,
        // corte del día y/o productos) marcados en el alta. El rol empleado no
        // otorga permisos base y nunca se le asigna admin.users.manage desde aquí.
        if ($role === 'empleado') {
            $modules = array_filter((array) ($data['modules'] ?? []));
            $finePermissions = array_filter((array) ($data['permissions'] ?? []));

            $finalPermissions = array_unique(
                array_merge(
                    User::permissionsForModules($modules),
                    array_intersect($finePermissions, User::assignablePermissions())
                )
            );

            // Solo asignar permisos que ya existen en la base de datos (evita
            // PermissionDoesNotExist si el seeder aún no se ha ejecutado).
            $existingPermissions = Permission::query()
                ->whereIn('name', $finalPermissions)
                ->pluck('name')
                ->all();

            $user->syncPermissions(array_values($existingPermissions));
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Usuario «{$user->name}» creado correctamente.");
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->roles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $role = $data['role'];

        unset($data['role']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$role]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Usuario «{$user->name}» actualizado correctamente.");
    }

    public function updatePermissions(UpdateEmployeePermissionsRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('empleado'), 403, 'Solo los empleados reciben permisos individuales.');

        $user->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Permisos de «{$user->name}» actualizados correctamente.");
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->hasRole('empleado'), 403, 'Solo se pueden eliminar empleados.');

        $name = $user->name;
        $email = $user->email;

        // 1. Movimientos de caja: user_id tiene restrictOnDelete y no es nullable,
        //    por lo que bloquean el borrado físico del usuario.
        DB::table('cash_movements')->where('user_id', $user->id)->delete();

        // 2. Ventas: user_id tiene restrictOnDelete y no es nullable.
        //    Al borrar la venta se cascada automáticamente a sale_items,
        //    sale_returns y sale_return_items (todas tienen cascadeOnDelete).
        DB::table('sales')->where('user_id', $user->id)->delete();

        // 3. Registros restantes que referencian al usuario.
        //    Se usa el query builder directo para evitar scopes de SoftDeletes
        //    en modelos como AuditLog y CashMovement que no tienen columna deleted_at.
        DB::table('stock_movements')->where('user_id', $user->id)->delete();
        DB::table('daily_cuts')
            ->where('user_id', $user->id)
            ->orWhere('generated_by', $user->id)
            ->delete();
        DB::table('customer_payments')->where('user_id', $user->id)->delete();
        DB::table('supplier_payments')->where('user_id', $user->id)->delete();
        DB::table('audit_logs')->where('user_id', $user->id)->delete();

        // 4. Borrado físico real (forceDelete): elimina el registro de la tabla
        //    users y libera el correo electrónico para poder registrarlo de nuevo.
        $user->forceDelete();

        return redirect()
            ->route('admin.users.index')
            ->with(
                'status',
                "Empleado «{$name}» ({$email}) eliminado definitivamente. Ahora puedes registrarlo de nuevo con ese correo si lo deseas."
            );
    }

    /**
     * @return Collection<int, Role>
     */
    private function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }
}
