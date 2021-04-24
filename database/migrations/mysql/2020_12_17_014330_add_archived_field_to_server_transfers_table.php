<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArchivedFieldToServerTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->boolean('archived')->default(0)->after('new_additional_allocations');
        });

        // Update archived to all be true on existing transfers.
        Schema::table('server_transfers', function (Blueprint $table) {
            DB::statement('UPDATE `server_transfers` SET `archived` = 1 WHERE `successful` = 1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->dropColumn('archived');
        });
    }
}
