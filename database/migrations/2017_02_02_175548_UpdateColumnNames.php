<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign('servers_node_foreign');
            $table->dropForeign('servers_owner_foreign');
            $table->dropForeign('servers_allocation_foreign');
            $table->dropForeign('servers_service_foreign');
            $table->dropForeign('servers_option_foreign');
            $table->dropForeign('servers_pack_foreign');

            $table->dropIndex('servers_node_foreign');
            $table->dropIndex('servers_owner_foreign');
            $table->dropIndex('servers_allocation_foreign');
            $table->dropIndex('servers_service_foreign');
            $table->dropIndex('servers_option_foreign');
            $table->dropIndex('servers_pack_foreign');

            $table->renameColumn('node', 'node_id');
            $table->renameColumn('owner', 'owner_id');
            $table->renameColumn('allocation', 'allocation_id');
            $table->renameColumn('service', 'service_id');
            $table->renameColumn('option', 'option_id');
            $table->renameColumn('pack', 'pack_id');

            $table->foreign('node_id')->references('id')->on('nodes');
            $table->foreign('owner_id')->references('id')->on('users');
            $table->foreign('allocation_id')->references('id')->on('allocations');
            $table->foreign('service_id')->references('id')->on('services');
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign('servers_node_id_foreign');
            $table->dropForeign('servers_owner_id_foreign');
            $table->dropForeign('servers_allocation_id_foreign');
            $table->dropForeign('servers_service_id_foreign');
            $table->dropForeign('servers_option_id_foreign');
            $table->dropForeign('servers_pack_id_foreign');

            $table->dropIndex('servers_node_id_foreign');
            $table->dropIndex('servers_owner_id_foreign');
            $table->dropIndex('servers_allocation_id_foreign');
            $table->dropIndex('servers_service_id_foreign');
            $table->dropIndex('servers_option_id_foreign');
            $table->dropIndex('servers_pack_id_foreign');

            $table->renameColumn('node_id', 'node');
            $table->renameColumn('owner_id', 'owner');
            $table->renameColumn('allocation_id', 'allocation');
            $table->renameColumn('service_id', 'service');
            $table->renameColumn('option_id', 'option');
            $table->renameColumn('pack_id', 'pack');

            $table->foreign('node')->references('id')->on('nodes');
            $table->foreign('owner')->references('id')->on('users');
            $table->foreign('allocation')->references('id')->on('allocations');
            $table->foreign('service')->references('id')->on('services');
            $table->foreign('option')->references('id')->on('service_options');
        });
    }
}
