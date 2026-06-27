<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('blog_unlocks');

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('required_star');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->integer('required_star')->default(0);
        });

        Schema::create('blog_unlocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['blog_id', 'user_id']);
            $table->index('blog_id');
            $table->index('user_id');
        });
    }
};
