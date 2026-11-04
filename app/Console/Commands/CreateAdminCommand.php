<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {email : Correo electrónico del administrador}
        {name : Nombre completo del administrador}
        {password : Contraseña del administrador}';

    protected $description = 'Crea o actualiza un administrador local con todos los permisos';

    public function handle(): int
    {
        if (! $this->confirmEnvironmentIsLocal()) {
            return self::FAILURE;
        }

        if (! Role::query()->where('name', 'admin')->where('guard_name', 'web')->exists()) {
            $this->callSilent('db:seed', [
                '--class' => RolePermissionSeeder::class,
                '--force' => true,
            ]);
        }

        $email = (string) $this->argument('email');
        $name = (string) $this->argument('name');
        $password = (string) $this->argument('password');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'is_active' => true,
            ],
        );

        $user->syncRoles(['admin']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Administrador listo: {$user->email}");

        return self::SUCCESS;
    }

    private function confirmEnvironmentIsLocal(): bool
    {
        if (app()->environment('local', 'testing')) {
            return true;
        }

        $this->error('Este comando solo puede ejecutarse en el entorno local o de pruebas.');

        return false;
    }
}
