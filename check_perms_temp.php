<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$user = User::find(2);
if ($user) {
    echo "Usuario: {$user->name} ({$user->email})\n";
    echo 'Activo: '.($user->is_active ? 'SÍ' : 'NO')."\n";
    echo "\n--- ROLES ---\n";
    foreach ($user->getRoleNames() as $role) {
        echo "  - {$role}\n";
    }
    echo "\n--- PERMISOS DIRECTOS ---\n";
    foreach ($user->getDirectPermissions()->pluck('name') as $perm) {
        echo "  - {$perm}\n";
    }
    echo "\n--- PERMISOS INCLUYENDO ROLES ---\n";
    foreach ($user->getAllPermissions()->pluck('name') as $perm) {
        echo "  - {$perm}\n";
    }

    echo "\n--- TIENE pos.cut.manage? ---\n";
    echo ($user->can('pos.cut.manage') ? 'SÍ' : 'NO')."\n";
} else {
    echo "Usuario ID 2 no encontrado\n";
}
