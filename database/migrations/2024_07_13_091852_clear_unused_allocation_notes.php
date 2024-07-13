<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('allocations')
            ->where('server_id', null)
            ->whereNot('notes', null)
            ->update(['notes' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse not needed
    }
};