<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create (or update) the bootstrap administrator account.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@motozone.com'],
            [
                'name' => 'motozone',
                'password' => 'admin123',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['admin']);

        $this->command?->info("Administrador listo: {$admin->email}");
    }
}
