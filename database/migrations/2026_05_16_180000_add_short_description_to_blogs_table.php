<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blogs')) {
            return;
        }

        Schema::table('blogs', function (Blueprint $table): void {
            if (! Schema::hasColumn('blogs', 'short_description')) {
                $table->string('short_description', 500)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('blogs')) {
            return;
        }

        Schema::table('blogs', function (Blueprint $table): void {
            if (Schema::hasColumn('blogs', 'short_description')) {
                $table->dropColumn('short_description');
            }
        });
    }
};
