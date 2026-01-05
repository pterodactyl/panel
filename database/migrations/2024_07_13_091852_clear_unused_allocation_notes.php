<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        DB::table('allocations')
            ->whereNull('server_id')
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
