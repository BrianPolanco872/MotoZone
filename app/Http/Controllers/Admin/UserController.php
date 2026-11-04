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

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Empleado «{$name}» eliminado correctamente.");
    }

    /**
     * @return Collection<int, Role>
     */
    private function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }
}
