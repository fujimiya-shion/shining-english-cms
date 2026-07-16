<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['courses', 'blogs', 'categories', 'quizzes', 'contacts', 'admins', 'users', 'orders'];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'order')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('order')->default(0)->index();
            });

            DB::statement("UPDATE {$table} SET `order` = UNIX_TIMESTAMP(created_at) WHERE `order` = 0");
        }
    }

    public function down(): void
    {
        $tables = ['courses', 'blogs', 'categories', 'quizzes', 'contacts', 'admins', 'users', 'orders'];

        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'order')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('order');
            });
        }
    }
};
