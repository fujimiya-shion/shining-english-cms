<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_reviews')) {
            return;
        }

        $this->removeDuplicateCourseReviews();

        Schema::table('course_reviews', function (Blueprint $table): void {
            $table->unique(['course_id', 'user_id'], 'course_reviews_course_user_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('course_reviews')) {
            return;
        }

        Schema::table('course_reviews', function (Blueprint $table): void {
            $table->dropUnique('course_reviews_course_user_unique');
        });
    }

    protected function removeDuplicateCourseReviews(): void
    {
        $duplicateGroups = DB::table('course_reviews')
            ->select('course_id', 'user_id')
            ->groupBy('course_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $duplicateGroup) {
            $reviewToKeepId = DB::table('course_reviews')
                ->where('course_id', $duplicateGroup->course_id)
                ->where('user_id', $duplicateGroup->user_id)
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->value('id');

            if ($reviewToKeepId === null) {
                continue;
            }

            DB::table('course_reviews')
                ->where('course_id', $duplicateGroup->course_id)
                ->where('user_id', $duplicateGroup->user_id)
                ->where('id', '!=', $reviewToKeepId)
                ->delete();
        }
    }
};
