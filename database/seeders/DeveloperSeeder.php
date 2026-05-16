<?php

namespace Database\Seeders;

use App\Models\Developer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeveloperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ACCESS_TOKEN_EMAIL');
        $password = env('ACCESS_TOKEN_PASSWORD');
        if(!$email || !$password) {
            $this->command->warn('DeveloperSeeder skipped: ACCESS_TOKEN_EMAIL or ACCESS_TOKEN_PASSWORD is missing.');
            return;
        }

        Developer::updateOrCreate([
            'email' => $email,
        ], [
            'password' => $password,
        ]);
    }
}
