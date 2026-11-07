@extends('layouts.admin')

@section('title', 'Empleados')
@section('subtitle', 'Altas, datos y permisos del equipo')

@section('content')
    @php
        $inputClass = 'mz-input mt-1.5 h-11 w-full';
        $labelClass = 'block text-sm font-medium text-mz-text-secondary';
        $errorClass = 'mt-1 text-xs font-medium text-mz-error';
    @endphp

    @if ($errors->any())
        <x-alert variant="error" class="mb-5">
            <p class="font-semibold">Revisa los siguientes campos:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <x-card padded>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-mz-text-primary">Agregar empleado</h2>
                <p class="text-xs text-mz-text-muted">Crea una cuenta de mostrador. Después asígnale sus permisos abajo.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="x-button x-button-secondary size-sm shrink-0">
                <x-icon name="plus" class="size-4" />
                Crear otro tipo de usuario
            </a>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="mt-5">
            @csrf
            <input type="hidden" name="role" value="empleado">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="{{ $labelClass }}">Nombre completo</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="{{ $inputClass }}" placeholder="Ana Ramírez">
                    @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="{{ $labelClass }}">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="{{ $inputClass }}" placeholder="ana@motozone.mx">
                    @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="{{ $labelClass }}">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="{{ $inputClass }}" placeholder="Mínimo 8 caracteres">
                    @error('password') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="{{ $labelClass }}">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $inputClass }}" placeholder="Repite la contraseña">
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm text-mz-text-secondary">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="mz-checkbox size-4">
                        Cuenta activa
                    </label>
                    @error('is_active') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <fieldset class="rounded-lg border border-mz-border-light p-3">
                        <legend class="mx-1 mb-2 w-auto px-1 text-xs font-semibold text-mz-text-secondary">Módulos de acceso</legend>
                        <p class="mb-3 text-xs text-mz-text-muted">Marca los módulos que este empleado podrá usar al iniciar sesión.</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @php
                                $accessModules = \App\Models\User::ACCESS_MODULES;
                                $selectedModules = old('modules', []);
                            @endphp
                            @foreach ($accessModules as $key => $module)
                                <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-mz-border-light bg-mz-bg-surface-2/60 px-3 py-2 text-sm text-mz-text-secondary transition hover:bg-mz-bg-surface-2">
                                    <input type="checkbox" name="modules[]" value="{{ $key }}" @checked(in_array($key, $selectedModules, true)) class="mz-checkbox mt-0.5 size-4">
                                    <div>
                                        <span class="block text-xs font-medium text-mz-text-primary">{{ $module['label'] }}</span>
                                        <span class="block text-[10px] leading-tight text-mz-text-muted">{{ $module['description'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('modules') <p class="{{ $errorClass }} mt-1">{{ $message }}</p> @enderror
                    </fieldset>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit" class="x-button x-button-primary size-sm">
                    <x-icon name="check" class="size-4" />
                    Agregar empleado
                </button>
            </div>
        </form>
    </x-card>

    <section class="mt-6 mz-card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-mz-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-mz-text-primary">Equipo y permisos</h2>
                <p class="text-xs text-mz-text-muted">Los permisos son individuales: lo que marques aquí es lo que podrá usar cada empleado.</p>
            </div>
            <p class="text-xs text-mz-text-muted">
                <span class="font-semibold text-mz-text-primary">{{ $users->total() }}</span>
                {{ $users->total() === 1 ? 'cuenta registrada' : 'cuentas registradas' }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="mz-table">
                <thead>
                    <tr>
                        <th scope="col">Empleado</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Permisos</th>
                        <th scope="col"><span class="sr-only">Editar</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $isEmployee = $user->hasRole('empleado');
                            $directPermissions = $user->permissions->pluck('name')->all();
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-mz-accent-primary text-xs font-semibold text-white">
                                        {{ strtoupper(str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('')) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-mz-text-primary">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))
                                                <span class="ml-1 rounded bg-mz-bg-surface-2 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-mz-text-muted">Tú</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs text-mz-text-muted">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                @forelse ($user->roles as $role)
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                        'bg-mz-accent-primary/10 text-mz-accent-primary ring-mz-accent-primary/20' => $role->name === 'admin',
                                        'bg-sky-500/10 text-sky-400 ring-sky-500/20' => $role->name !== 'admin',
                                    ])>{{ ucfirst($role->name) }}</span>
                                @empty
                                    <span class="text-xs text-mz-text-muted">Sin rol</span>
                                @endforelse
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    'bg-mz-success/10 text-mz-success ring-mz-success/20' => $user->is_active,
                                    'bg-mz-text-muted/10 text-mz-text-muted ring-mz-text-muted/20' => ! $user->is_active,
                                ])>{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($isEmployee)
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($directPermissions as $permission)
                                            <span class="inline-flex rounded-full bg-mz-bg-surface-2 px-2.5 py-0.5 text-xs font-medium text-mz-text-secondary">
                                                {{ $permissionLabels[$permission] ?? $permission }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-mz-text-muted">Sin permisos</span>
                                        @endforelse
                                    </div>
                                @else
                                    <span class="text-xs text-mz-text-muted">Acceso completo por rol</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="x-button x-button-secondary size-xs">
                                        <x-icon name="pencil" class="size-3.5" />
                                        Editar
                                    </a>

                                    @if ($isEmployee)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar este empleado? Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="x-button x-button-ghost size-xs text-mz-error">
                                                <x-icon name="trash" class="size-3.5" />
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if ($isEmployee)
                            <tr class="bg-mz-bg-surface-2/40">
                                <td colspan="5" class="px-5 pb-4 pt-0">
                                    <details class="group">
                                        <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-md border border-mz-border-light bg-mz-bg-surface px-3 py-1.5 text-xs font-medium text-mz-text-secondary transition hover:bg-mz-bg-surface-2 hover:text-mz-text-primary [&::-webkit-details-marker]:hidden">
                                            <x-icon name="shield" class="size-3.5" />
                                            Asignar permisos
                                            <x-icon name="chevron-down" class="size-3.5 transition group-open:rotate-180" />
                                        </summary>

                                        <form action="{{ route('admin.users.permissions.update', $user) }}" method="POST" class="mt-3 rounded-lg border border-mz-border-light bg-mz-bg-surface p-4">
                                            @csrf
                                            @method('PUT')

                                            <div class="space-y-4">
                                                @foreach ($permissionCatalog as $group => $groupPermissions)
                                                    <fieldset>
                                                        <legend class="text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">{{ $group }}</legend>
                                                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                            @foreach ($groupPermissions as $permission => $details)
                                                                <label class="flex items-start gap-3 rounded-lg border border-mz-border-light bg-mz-bg-surface-2/60 px-3 py-2.5 text-sm text-mz-text-secondary transition hover:bg-mz-bg-surface-2">
                                                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', $directPermissions), true)) class="mz-checkbox mt-0.5 size-4">
                                                                    <span>
                                                                        <span class="font-medium text-mz-text-primary">{{ $details['label'] }}</span>
                                                                        <span class="block text-xs text-mz-text-muted">{{ $details['description'] }}</span>
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </fieldset>
                                                @endforeach
                                            </div>

                                            <div class="mt-4 flex justify-end">
                                                <button type="submit" class="x-button x-button-primary size-xs">
                                                    <x-icon name="check" class="size-3.5" />
                                                    Guardar permisos
                                                </button>
                                            </div>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-mz-text-secondary">Aún no hay cuentas registradas</p>
                                <p class="mt-1 text-xs text-mz-text-muted">Usa el formulario de arriba para agregar al primer empleado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
@endsection
