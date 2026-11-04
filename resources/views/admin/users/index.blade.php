@extends('layouts.admin')

@section('title', 'Empleados')
@section('subtitle', 'Altas, datos y permisos del equipo')

@section('content')
    @php
        $inputClass = 'mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20';
        $labelClass = 'block text-sm font-medium text-slate-700';
        $errorClass = 'mt-1 text-xs font-medium text-rose-600';
    @endphp

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
            <p class="font-semibold">Revisa los siguientes campos:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Agregar empleado</h2>
                <p class="text-xs text-slate-500">Crea una cuenta de mostrador. Después asígnale sus permisos abajo.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
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
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Cuenta activa
                    </label>
                    @error('is_active') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <x-icon name="check" class="size-4" />
                    Agregar empleado
                </button>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Equipo y permisos</h2>
                <p class="text-xs text-slate-500">Los permisos son individuales: lo que marques aquí es lo que podrá usar cada empleado.</p>
            </div>
            <p class="text-xs text-slate-500">
                <span class="font-semibold text-slate-900">{{ $users->total() }}</span>
                {{ $users->total() === 1 ? 'cuenta registrada' : 'cuentas registradas' }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-5 py-3 font-medium">Empleado</th>
                        <th scope="col" class="px-5 py-3 font-medium">Rol</th>
                        <th scope="col" class="px-5 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-5 py-3 font-medium">Permisos</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Editar</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        @php
                            $isEmployee = $user->hasRole('empleado');
                            $directPermissions = $user->permissions->pluck('name')->all();
                        @endphp
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs font-semibold text-white">
                                        {{ strtoupper(str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('')) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))
                                                <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-500">Tú</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                @forelse ($user->roles as $role)
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                        'bg-brand-50 text-brand-700 ring-brand-600/20' => $role->name === 'admin',
                                        'bg-sky-50 text-sky-700 ring-sky-600/20' => $role->name !== 'admin',
                                    ])>{{ ucfirst($role->name) }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">Sin rol</span>
                                @endforelse
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $user->is_active,
                                    'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $user->is_active,
                                ])>{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($isEmployee)
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($directPermissions as $permission)
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                                {{ $permissionLabels[$permission] ?? $permission }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400">Sin permisos</span>
                                        @endforelse
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Acceso completo por rol</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-slate-600 transition hover:border-brand-500 hover:text-brand-700">
                                        <x-icon name="pencil" class="size-3.5" />
                                        Editar
                                    </a>

                                    @if ($isEmployee)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar este empleado? Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-rose-200 px-2.5 text-xs font-medium text-rose-600 transition hover:border-rose-400 hover:bg-rose-50">
                                                <x-icon name="trash" class="size-3.5" />
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if ($isEmployee)
                            <tr class="bg-slate-50/60">
                                <td colspan="5" class="px-5 pb-4 pt-0">
                                    <details class="group">
                                        <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-brand-500 hover:text-brand-700 [&::-webkit-details-marker]:hidden">
                                            <x-icon name="shield" class="size-3.5" />
                                            Asignar permisos
                                            <x-icon name="chevron-down" class="size-3.5 transition group-open:rotate-180" />
                                        </summary>

                                        <form action="{{ route('admin.users.permissions.update', $user) }}" method="POST" class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                                            @csrf
                                            @method('PUT')

                                            <div class="space-y-4">
                                                @foreach ($permissionCatalog as $group => $groupPermissions)
                                                    <fieldset>
                                                        <legend class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $group }}</legend>
                                                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                            @foreach ($groupPermissions as $permission => $details)
                                                                <label class="flex items-start gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', $directPermissions), true)) class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                                    <span>
                                                                        <span class="font-medium text-slate-900">{{ $details['label'] }}</span>
                                                                        <span class="block text-xs text-slate-500">{{ $details['description'] }}</span>
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </fieldset>
                                                @endforeach
                                            </div>

                                            <div class="mt-4 flex justify-end">
                                                <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-700 px-4 text-xs font-semibold text-white transition hover:bg-brand-800">
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
                                <p class="text-sm font-medium text-slate-700">Aún no hay cuentas registradas</p>
                                <p class="mt-1 text-xs text-slate-500">Usa el formulario de arriba para agregar al primer empleado.</p>
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
