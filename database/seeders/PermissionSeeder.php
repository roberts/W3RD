<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-everything',
            'play-all-games',
            'play-board-games',
            'play-card-games',
            'play-solo-games',
            'create-clients',
            'edit-own-clients',
            'create-avatars',
            'create-badges',
            'create-registration',
            'view-client-dashboard',
            'edit-any-user-profile',
            'can-update-username',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superadmin = Role::findByName('Superadmin');
        $superadmin->givePermissionTo(Permission::all());

        $clientAdmin = Role::findByName('Client Admin');
        $clientAdmin->givePermissionTo([
            'play-all-games',
            'play-board-games',
            'play-card-games',
            'play-solo-games',
            'create-clients',
            'edit-own-clients',
            'create-avatars',
            'create-badges',
            'create-registration',
            'view-client-dashboard',
            'edit-any-user-profile',
            'can-update-username',
        ]);

        $clientMember = Role::findByName('Client Member');
        $clientMember->givePermissionTo([
            'play-all-games',
            'play-board-games',
            'play-card-games',
            'play-solo-games',
            'edit-own-clients',
            'create-avatars',
            'create-badges',
            'create-registration',
            'view-client-dashboard',
            'edit-any-user-profile',
            'can-update-username',
        ]);

        $masterPlayer = Role::findByName('Master Player');
        $masterPlayer->givePermissionTo([
            'create-clients',
            'create-avatars',
            'can-update-username',
        ]);

        $player = Role::findByName('Player');
        $player->givePermissionTo([
            'play-all-games',
            'play-board-games',
            'play-card-games',
            'play-solo-games',
        ]);
    }
}
