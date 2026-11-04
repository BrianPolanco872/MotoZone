@php
    $isEdit = $user->exists;
    $isSelf = $isEdit && $user->is(auth()->user());
    $currentRole = old('role', $user->roles->first()?->name ?? 'empleado');
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

<form action="{{ $action }}" method="POST" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Datos de la cuenta</h2>

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
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

            <div>
                <label for="password" class="{{ $labelClass }}">Contraseña {{ $isEdit ? '(opcional)' : '' }}</label>
                <input id="password" name="password" type="password" autocomplete="new-password" @required(! $isEdit) class="{{ $inputClass }}" placeholder="{{ $isEdit ? 'Dejar en blanco para conservar' : 'Mínimo 8 caracteres' }}">
                @error('password') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="{{ $labelClass }}">Confirmar contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $isEdit) class="{{ $inputClass }}" placeholder="Repite la contraseña">
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Rol y acceso</h2>
        <p class="text-xs text-slate-500">El rol define los permisos; el estado controla si puede iniciar sesión.</p>

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="role" class="{{ $labelClass }}">Rol</label>
                <select id="role" name="role" required class="{{ $inputClass }}">
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected($currentRole === $role->name)>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
                @error('role') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-end">
                <label class="flex items-start gap-3 pb-2.5 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $user->is_active : true)) class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>
                        <span class="font-medium">Cuenta activa</span>
                        <span class="block text-xs text-slate-500">Si la desactivas, no podrá iniciar sesión.</span>
                    </span>
                </label>
                @error('is_active') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($isSelf)
            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-inset ring-amber-600/20">
                No puedes desactivar tu propia cuenta ni quitarte el rol de administrador.
            </p>
        @endif
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.users.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancelar</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-800">
            <x-icon name="check" class="size-4" />
            {{ $submitLabel }}
        </button>
    </div>
</form>
