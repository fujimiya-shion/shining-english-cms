<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        Admin::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'email' => $email,
                'password' => Hash::make($password),
            ]
        );
    }
}
