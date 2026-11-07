@php
    $isEdit = $user->exists;
    $isSelf = $isEdit && $user->is(auth()->user());
    $currentRole = old('role', $user->roles->first()?->name ?? 'empleado');
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

<form action="{{ $action }}" method="POST" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-card padded>
        <div>
            <h2 class="text-base font-semibold text-mz-text-primary">Datos de la cuenta</h2>
            <p class="text-xs text-mz-text-muted">Información con la que el usuario inicia sesión.</p>
        </div>

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
    </x-card>

    <x-card padded>
        <div>
            <h2 class="text-base font-semibold text-mz-text-primary">Rol y acceso</h2>
            <p class="text-xs text-mz-text-muted">El rol define los permisos; el estado controla si puede iniciar sesión.</p>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="role" class="{{ $labelClass }}">Rol</label>
                <select id="role" name="role" required class="mz-select mt-1.5 h-11 w-full">
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected($currentRole === $role->name)>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
                @error('role') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-end">
                <label class="flex items-start gap-3 pb-2.5 text-sm text-mz-text-secondary">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $user->is_active : true)) class="mz-checkbox mt-0.5 size-4">
                    <span>
                        <span class="font-medium text-mz-text-primary">Cuenta activa</span>
                        <span class="block text-xs text-mz-text-muted">Si la desactivas, no podrá iniciar sesión.</span>
                    </span>
                </label>
                @error('is_active') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($isSelf)
            <p class="mt-4 rounded-lg bg-mz-warning/10 px-3 py-2 text-xs text-mz-warning ring-1 ring-inset ring-mz-warning/20">
                No puedes desactivar tu propia cuenta ni quitarte el rol de administrador.
            </p>
        @endif
    </x-card>

    @if (! $isEdit)
        <x-card padded>
            <div>
                <h2 class="text-base font-semibold text-mz-text-primary">Módulos de acceso</h2>
                <p class="text-xs text-mz-text-muted">Marca los módulos que este empleado podrá usar al iniciar sesión.</p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
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
        </x-card>
    @endif

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.users.index') }}" class="x-button x-button-secondary size-sm">
            Cancelar
        </a>
        <button type="submit" class="x-button x-button-primary size-sm">
            <x-icon name="check" class="size-4" />
            {{ $submitLabel }}
        </button>
    </div>
</form>
