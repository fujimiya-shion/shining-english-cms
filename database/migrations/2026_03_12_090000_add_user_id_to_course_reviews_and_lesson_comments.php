<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_reviews') && ! Schema::hasColumn('course_reviews', 'user_id')) {
            Schema::table('course_reviews', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->after('course_id');
                $table->index('user_id');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('lesson_comments') && ! Schema::hasColumn('lesson_comments', 'user_id')) {
            Schema::table('lesson_comments', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->after('lesson_id');
                $table->index('user_id');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_reviews') && Schema::hasColumn('course_reviews', 'user_id')) {
            Schema::table('course_reviews', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('lesson_comments') && Schema::hasColumn('lesson_comments', 'user_id')) {
            Schema::table('lesson_comments', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
