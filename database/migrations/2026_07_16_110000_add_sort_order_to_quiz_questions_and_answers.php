<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('content');
            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('content');
            $table->index(['quiz_question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropIndex(['quiz_question_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
