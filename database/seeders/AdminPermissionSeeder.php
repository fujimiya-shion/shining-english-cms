<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionSeeder extends Seeder
{
    /**
     * Seed admin roles and permissions required for CMS access.
     */
    public function run(): void
    {
        $guardName = 'admin';
        $superAdminRoleName = config('filament-shield.super_admin.name', 'super_admin');

        $superAdminRole = Role::query()->firstOrCreate([
            'name' => $superAdminRoleName,
            'guard_name' => $guardName,
        ]);

        $apiDocsPermission = Permission::query()->firstOrCreate([
            'name' => 'View:ApiDocs',
            'guard_name' => $guardName,
        ]);

        $superAdminRole->givePermissionTo($apiDocsPermission);
    }
}
