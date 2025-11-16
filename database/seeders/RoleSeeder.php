<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'Superadmin',
            'Client Admin',
            'Client Member',
            'Master Player',
            'Player',
            'Suspended',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role]);
        }
    }
}
