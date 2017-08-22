<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateNodesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign('nodes_location_foreign');
            $table->dropIndex('nodes_location_foreign');

            $table->renameColumn('location', 'location_id');
            $table->foreign('location_id')->references('id')->on('locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign('nodes_location_id_foreign');
            $table->dropIndex('nodes_location_id_foreign');

            $table->renameColumn('location_id', 'location');
            $table->foreign('location')->references('id')->on('locations');
        });
    }
}
