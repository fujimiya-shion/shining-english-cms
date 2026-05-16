<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_groups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'sort_order'], 'lesson_groups_course_sort_order_idx');
            $table->unique(['course_id', 'name'], 'lesson_groups_course_name_unique');
        });

        Schema::table('lessons', function (Blueprint $table): void {
            $table->unsignedBigInteger('lesson_group_id')->nullable()->after('course_id');
            $table->index('lesson_group_id');
            $table->foreign('lesson_group_id')
                ->references('id')
                ->on('lesson_groups')
                ->nullOnDelete();
        });

        $courses = DB::table('lessons')
            ->select('course_id')
            ->distinct()
            ->pluck('course_id');

        foreach ($courses as $courseId) {
            $rows = DB::table('lessons')
                ->where('course_id', $courseId)
                ->select('group_name', DB::raw('MIN(group_order) as group_order'))
                ->groupBy('group_name')
                ->orderByRaw('MIN(group_order)')
                ->get();

            foreach ($rows as $row) {
                $groupName = trim((string) ($row->group_name ?? ''));
                if ($groupName === '') {
                    continue;
                }

                $groupId = DB::table('lesson_groups')->insertGetId([
                    'course_id' => $courseId,
                    'name' => $groupName,
                    'sort_order' => max(1, (int) ($row->group_order ?? 1)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('lessons')
                    ->where('course_id', $courseId)
                    ->where('group_name', $groupName)
                    ->update([
                        'lesson_group_id' => $groupId,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropForeign(['lesson_group_id']);
            $table->dropIndex(['lesson_group_id']);
            $table->dropColumn('lesson_group_id');
        });

        Schema::dropIfExists('lesson_groups');
    }
};
