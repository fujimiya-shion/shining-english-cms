<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->unsignedInteger('group_order')->default(0)->after('group_name');
            $table->unsignedInteger('lesson_order')->default(0)->after('group_order');

            $table->index(['course_id', 'group_order'], 'lessons_course_group_order_idx');
            $table->index(['course_id', 'group_order', 'lesson_order'], 'lessons_course_group_lesson_order_idx');
        });

        $courseIds = DB::table('lessons')
            ->select('course_id')
            ->distinct()
            ->pluck('course_id');

        foreach ($courseIds as $courseId) {
            $lessons = DB::table('lessons')
                ->where('course_id', $courseId)
                ->orderByRaw('COALESCE(group_name, "")')
                ->orderBy('id')
                ->get(['id', 'group_name']);

            $groupIndexMap = [];
            $lessonIndexMap = [];
            $nextGroupOrder = 1;

            foreach ($lessons as $lesson) {
                $groupName = trim((string) ($lesson->group_name ?? ''));

                if (! array_key_exists($groupName, $groupIndexMap)) {
                    $groupIndexMap[$groupName] = $nextGroupOrder++;
                    $lessonIndexMap[$groupName] = 1;
                }

                DB::table('lessons')
                    ->where('id', $lesson->id)
                    ->update([
                        'group_order' => $groupIndexMap[$groupName],
                        'lesson_order' => $lessonIndexMap[$groupName],
                    ]);

                $lessonIndexMap[$groupName]++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropIndex('lessons_course_group_order_idx');
            $table->dropIndex('lessons_course_group_lesson_order_idx');
            $table->dropColumn(['group_order', 'lesson_order']);
        });
    }
};
