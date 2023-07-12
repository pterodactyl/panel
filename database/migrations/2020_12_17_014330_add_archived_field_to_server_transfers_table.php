<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArchivedFieldToServerTransfersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->boolean('archived')->default(0)->after('new_additional_allocations');
        });

        // Update archived to all be true on existing transfers.
        DB::table('server_transfers')->where('successful', true)->update(['archived' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->dropColumn('archived');
        });
    }
}
