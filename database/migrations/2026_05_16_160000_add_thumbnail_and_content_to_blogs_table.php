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
            if (! Schema::hasColumn('blogs', 'thumbnail')) {
                $table->text('thumbnail')->nullable()->after('description');
            }

            if (! Schema::hasColumn('blogs', 'content')) {
                $table->longText('content')->nullable()->after('thumbnail');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('blogs')) {
            return;
        }

        Schema::table('blogs', function (Blueprint $table): void {
            if (Schema::hasColumn('blogs', 'content')) {
                $table->dropColumn('content');
            }

            if (Schema::hasColumn('blogs', 'thumbnail')) {
                $table->dropColumn('thumbnail');
            }
        });
    }
};
