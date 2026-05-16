<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_star_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id');
            $table->string('source', 32);
            $table->unsignedInteger('amount');
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id', 'source'], 'lesson_star_rewards_user_lesson_source_unique');
            $table->index(['course_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_star_rewards');
    }
};
