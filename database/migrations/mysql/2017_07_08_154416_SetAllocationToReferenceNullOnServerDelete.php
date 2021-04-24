<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetAllocationToReferenceNullOnServerDelete extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['server_id']);

            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['server_id']);

            $table->foreign('server_id')->references('id')->on('servers');
        });
    }
}
