<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backward-compatibility shim:
        // this migration file was removed and re-added so old environments can rollback.
        // Keep up() as no-op to avoid re-introducing deprecated columns on fresh databases.
    }

    public function down(): void
    {
        if (! Schema::hasTable('enrollments')) {
            return;
        }

        $deprecatedColumns = [];

        if (Schema::hasColumn('enrollments', 'current_lesson_id')) {
            $deprecatedColumns[] = 'current_lesson_id';
        }

        if (Schema::hasColumn('enrollments', 'completed_lesson_ids')) {
            $deprecatedColumns[] = 'completed_lesson_ids';
        }

        if ($deprecatedColumns === []) {
            return;
        }

        Schema::table('enrollments', function (Blueprint $table) use ($deprecatedColumns): void {
            $table->dropColumn($deprecatedColumns);
        });
    }
};
