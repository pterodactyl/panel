<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameColumns extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign('allocations_node_foreign');
            $table->dropForeign('allocations_assigned_to_foreign');
            $table->dropIndex('allocations_node_foreign');
            $table->dropIndex('allocations_assigned_to_foreign');

            $table->renameColumn('node', 'node_id');
            $table->renameColumn('assigned_to', 'server_id');
            $table->foreign('node_id')->references('id')->on('nodes');
            $table->foreign('server_id')->references('id')->on('servers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign('allocations_node_id_foreign');
            $table->dropForeign('allocations_server_id_foreign');
            $table->dropIndex('allocations_node_id_foreign');
            $table->dropIndex('allocations_server_id_foreign');

            $table->renameColumn('node_id', 'node');
            $table->renameColumn('server_id', 'assigned_to');
            $table->foreign('node')->references('id')->on('nodes');
            $table->foreign('assigned_to')->references('id')->on('servers');
        });
    }
}
