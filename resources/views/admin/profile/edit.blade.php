@extends('layouts.admin')

@section('title', 'Mi perfil')
@section('subtitle', 'Tus datos de acceso y los de tu equipo')

@section('content')
    @php
        $inputClass = 'mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20';
        $labelClass = 'block text-sm font-medium text-slate-700';
        $errorClass = 'mt-1 text-xs font-medium text-rose-600';
        $canManageEmployees = $user->can('admin.users.manage');
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

    <div class="max-w-4xl space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Mis datos</h2>
                <p class="text-xs text-slate-500">Actualiza tu nombre, correo y contraseña de acceso.</p>
            </div>

            <div class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-inset ring-amber-600/20">
                Tu nombre también sirve para iniciar sesión: si lo cambias, deberás escribir el nuevo en tu próximo acceso.
            </div>

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

                <fieldset class="rounded-xl border border-slate-200 p-4">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Cambiar contraseña (opcional)</legend>

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
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-800">
                        <x-icon name="check" class="size-4" />
                        Guardar mis datos
                    </button>
                </div>
            </form>
        </section>

        @if ($canManageEmployees)
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Datos de empleados</h2>
                        <p class="text-xs text-slate-500">Corrige el nombre o correo de un empleado, o asígnale una contraseña nueva.</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                        <x-icon name="shield" class="size-4" />
                        Gestionar permisos
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($employees as $employee)
                        <div class="px-5 py-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $employee->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $employee->email }}</p>
                                </div>
                                <span @class([
                                    'inline-flex w-fit rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $employee->is_active,
                                    'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $employee->is_active,
                                ])>{{ $employee->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </div>

                            <details class="group mt-3">
                                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-brand-500 hover:text-brand-700 [&::-webkit-details-marker]:hidden">
                                    <x-icon name="pencil" class="size-3.5" />
                                    Editar datos
                                    <x-icon name="chevron-down" class="size-3.5 transition group-open:rotate-180" />
                                </summary>

                                <form action="{{ route('admin.profile.employees.update', $employee) }}" method="POST" class="mt-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
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
                                        <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-700 px-4 text-xs font-semibold text-white transition hover:bg-brand-800">
                                            <x-icon name="check" class="size-3.5" />
                                            Guardar datos
                                        </button>
                                    </div>
                                </form>
                            </details>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-medium text-slate-700">Aún no hay empleados registrados</p>
                            <a href="{{ route('admin.users.index') }}" class="mt-2 inline-block text-xs font-medium text-brand-700 hover:text-brand-800">Agregar el primer empleado</a>
                        </div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
@endsection
