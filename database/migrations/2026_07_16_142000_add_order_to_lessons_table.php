<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lessons', 'order')) {
            return;
        }

        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedBigInteger('order')->default(0)->index()->after('quiz_id');
        });

        DB::statement('UPDATE lessons SET `order` = `id` WHERE `order` = 0');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lessons', 'order')) {
            return;
        }

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
