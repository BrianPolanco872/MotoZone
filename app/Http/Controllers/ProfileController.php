<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateEmployeeProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $employees = $user->can('admin.users.manage')
            ? User::query()->role('empleado')->orderBy('name')->get()
            : new Collection;

        return view('admin.profile.edit', [
            'user' => $user,
            'employees' => $employees,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        unset($data['current_password'], $data['password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.profile.edit')
            ->with('status', 'Tus datos se actualizaron correctamente.');
    }

    public function updateEmployee(UpdateEmployeeProfileRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('empleado'), 403, 'Solo puedes editar los datos de un empleado.');

        $data = $request->validated();

        unset($data['password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.profile.edit')
            ->with('status', "Datos de «{$user->name}» actualizados correctamente.");
    }
}
