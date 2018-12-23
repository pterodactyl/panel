<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnNames extends Migration
{
    /**
     * Run the migrations.
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

            // Pack ID was forgotten until multiple releases later, therefore it is
            // contained in '2017_03_18_204953_AddForeignKeyToPacks'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['allocation_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['option_id']);

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
            $table->foreign('pack')->references('id')->on('service_packs');
        });
    }
}
