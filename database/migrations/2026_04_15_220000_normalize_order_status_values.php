<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->whereNotNull('status')
            ->update([
                'status' => DB::raw('LOWER(TRIM(status))'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
