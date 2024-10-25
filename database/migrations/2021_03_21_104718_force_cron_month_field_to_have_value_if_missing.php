<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ForceCronMonthFieldToHaveValueIfMissing extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('schedules')->where('cron_month', '')->update(['cron_month' => '*']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down function.
    }
}
