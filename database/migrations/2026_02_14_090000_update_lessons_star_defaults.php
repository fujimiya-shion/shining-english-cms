<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE lessons MODIFY star_reward_video INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE lessons MODIFY star_reward_quiz INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE lessons MODIFY has_quiz TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE lessons MODIFY star_reward_video INT NOT NULL');
        DB::statement('ALTER TABLE lessons MODIFY star_reward_quiz INT NOT NULL');
        DB::statement('ALTER TABLE lessons MODIFY has_quiz TINYINT(1) NOT NULL DEFAULT 0');
    }
};
