@extends('layouts.admin')

@section('title', 'Mi perfil')
@section('subtitle', 'Tus datos de acceso y los de tu equipo')

@section('content')
    @php
        $inputClass = 'mz-input mt-1.5 h-11 w-full';
        $labelClass = 'block text-sm font-medium text-mz-text-secondary';
        $errorClass = 'mt-1 text-xs font-medium text-mz-error';
        $canManageEmployees = $user->can('admin.users.manage');
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

    <div class="max-w-4xl space-y-6">
        <x-card padded>
            <div>
                <h2 class="text-base font-semibold text-mz-text-primary">Mis datos</h2>
                <p class="text-xs text-mz-text-muted">Actualiza tu nombre, correo y contraseña de acceso.</p>
            </div>

            <x-alert variant="warning" class="mt-4">
                Tu nombre también sirve para iniciar sesión: si lo cambias, deberás escribir el nuevo en tu próximo acceso.
            </x-alert>

            <form action="{{ route('admin.profile.update') }}" method="POST" class="mt-5 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="{{ $labelClass }}">Nombre completo</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="{{ $inputClass }}" placeholder="Ana Ramírez">
                        @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="{{ $labelClass }}">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="{{ $inputClass }}" placeholder="ana@motozone.mx">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>

                <fieldset class="rounded-lg border border-mz-border-light p-4">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-[0.12em] text-mz-text-muted">Cambiar contraseña (opcional)</legend>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="current_password" class="{{ $labelClass }}">Contraseña actual</label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="{{ $inputClass }}" placeholder="Solo si vas a cambiarla">
                            @error('current_password') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="{{ $labelClass }}">Nueva contraseña</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" class="{{ $inputClass }}" placeholder="Mínimo 8 caracteres">
                            @error('password') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="{{ $labelClass }}">Confirmar contraseña</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $inputClass }}" placeholder="Repite la contraseña">
                        </div>
                    </div>
                </fieldset>

                <div class="flex justify-end">
                    <button type="submit" class="x-button x-button-primary size-sm">
                        <x-icon name="check" class="size-4" />
                        Guardar mis datos
                    </button>
                </div>
            </form>
        </x-card>

        @if ($canManageEmployees)
            <section class="mz-card overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-mz-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-mz-text-primary">Datos de empleados</h2>
                        <p class="text-xs text-mz-text-muted">Corrige el nombre o correo de un empleado, o asígnale una contraseña nueva.</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="x-button x-button-secondary size-sm shrink-0">
                        <x-icon name="shield" class="size-4" />
                        Gestionar permisos
                    </a>
                </div>

                <div class="divide-y divide-mz-border">
                    @forelse ($employees as $employee)
                        <div class="px-5 py-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-mz-text-primary">{{ $employee->name }}</p>
                                    <p class="truncate text-xs text-mz-text-muted">{{ $employee->email }}</p>
                                </div>
                                <span @class([
                                    'inline-flex w-fit rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    'bg-mz-success/10 text-mz-success ring-mz-success/20' => $employee->is_active,
                                    'bg-mz-text-muted/10 text-mz-text-muted ring-mz-text-muted/20' => ! $employee->is_active,
                                ])>{{ $employee->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </div>

                            <details class="group mt-3">
                                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-md border border-mz-border-light bg-mz-bg-surface px-3 py-1.5 text-xs font-medium text-mz-text-secondary transition hover:bg-mz-bg-surface-2 hover:text-mz-text-primary [&::-webkit-details-marker]:hidden">
                                    <x-icon name="pencil" class="size-3.5" />
                                    Editar datos
                                    <x-icon name="chevron-down" class="size-3.5 transition group-open:rotate-180" />
                                </summary>

                                <form action="{{ route('admin.profile.employees.update', $employee) }}" method="POST" class="mt-3 rounded-lg border border-mz-border-light bg-mz-bg-surface-2/40 p-4">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label for="employee-{{ $employee->id }}-name" class="{{ $labelClass }}">Nombre completo</label>
                                            <input id="employee-{{ $employee->id }}-name" name="name" type="text" value="{{ old('name', $employee->name) }}" required class="{{ $inputClass }}">
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label for="employee-{{ $employee->id }}-email" class="{{ $labelClass }}">Correo electrónico</label>
                                            <input id="employee-{{ $employee->id }}-email" name="email" type="email" value="{{ old('email', $employee->email) }}" required class="{{ $inputClass }}">
                                        </div>

                                        <div>
                                            <label for="employee-{{ $employee->id }}-password" class="{{ $labelClass }}">Nueva contraseña (opcional)</label>
                                            <input id="employee-{{ $employee->id }}-password" name="password" type="password" autocomplete="new-password" class="{{ $inputClass }}" placeholder="Dejar en blanco para conservar">
                                        </div>

                                        <div>
                                            <label for="employee-{{ $employee->id }}-password-confirmation" class="{{ $labelClass }}">Confirmar contraseña</label>
                                            <input id="employee-{{ $employee->id }}-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $inputClass }}" placeholder="Repite la contraseña">
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <button type="submit" class="x-button x-button-primary size-xs">
                                            <x-icon name="check" class="size-3.5" />
                                            Guardar datos
                                        </button>
                                    </div>
                                </form>
                            </details>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-medium text-mz-text-secondary">Aún no hay empleados registrados</p>
                            <a href="{{ route('admin.users.index') }}" class="mt-2 inline-block text-xs font-medium text-mz-accent-primary hover:text-mz-accent-hover">Agregar el primer empleado</a>
                        </div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
@endsection
