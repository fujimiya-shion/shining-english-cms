<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@shining-english.edu.vn';
        $password = '123456';
        if (!app()->environment('local')) {
            $email = env('ADMIN_EMAIL');
            $password = env('ADMIN_PASSWORD');
        }

        $admin = Admin::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'email' => $email,
                'password' => Hash::make($password),
            ]
        );

        $superAdminRoleName = config('filament-shield.super_admin.name', 'super_admin');
        $superAdminRole = Role::query()->firstOrCreate([
            'name' => $superAdminRoleName,
            'guard_name' => 'admin',
        ]);

        $admin->assignRole($superAdminRole);
    }
}
