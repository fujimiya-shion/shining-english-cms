<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_reviews')) {
            return;
        }

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
};
