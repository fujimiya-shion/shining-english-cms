<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'Sơ cấp',
            'Cơ bản',
            'Trung cấp',
            'Trung cấp nâng cao',
            'Nâng cao',
        ];

        foreach ($levels as $name) {
            Level::query()->firstOrCreate(['name' => $name], ['slug' => null]);
        }
    }
}
