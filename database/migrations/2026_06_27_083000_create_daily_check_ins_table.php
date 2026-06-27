<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_check_ins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('checked_in_at');
            $table->integer('reward_amount')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_check_ins');
    }
};
