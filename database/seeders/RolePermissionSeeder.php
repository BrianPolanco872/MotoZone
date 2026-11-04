<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'pos.access',
            'pos.sell',
            'pos.price.edit',
            'pos.discount',
            'pos.cancel.sale',
            'pos.wholesale',
            'pos.cash.manage',
            'pos.tickets.print.own',
            'pos.tickets.print.any',
            'pos.sales.day.own',
            'pos.sales.day.any',
            'pos.returns.request',
            'pos.returns.approve',
            'pos.return.do',
            'pos.cut.manage',
            'pos.cut.own',
            'admin.products.manage',
            'pos.products.view',
            'pos.products.edit',
            'admin.users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // El módulo de roles se retiró del panel: el permiso ya no tiene uso.
        Permission::query()->where('name', 'admin.roles.manage')->delete();

        $admin = Role::findOrCreate('admin', 'web');
        $employee = Role::findOrCreate('empleado', 'web');

        // El administrador conserva todos los permisos, incluidos los operativos del POS.
        $admin->syncPermissions($permissions);

        // El rol de empleado no otorga permisos base: el administrador los asigna
        // por usuario desde el panel de empleados.
        $employee->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
