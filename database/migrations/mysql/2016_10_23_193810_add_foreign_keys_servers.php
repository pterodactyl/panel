<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysServers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE servers
            MODIFY COLUMN node INT(10) UNSIGNED NOT NULL,
            MODIFY COLUMN owner INT(10) UNSIGNED NOT NULL,
            MODIFY COLUMN allocation INT(10) UNSIGNED NOT NULL,
            MODIFY COLUMN service INT(10) UNSIGNED NOT NULL,
            MODIFY COLUMN `option` INT(10) UNSIGNED NOT NULL
        ');

        Schema::table('servers', function (Blueprint $table) {
            $table->foreign('node')->references('id')->on('nodes');
            $table->foreign('owner')->references('id')->on('users');
            $table->foreign('allocation')->references('id')->on('allocations');
            $table->foreign('service')->references('id')->on('services');
            $table->foreign('option')->references('id')->on('service_options');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign('servers_node_foreign');
            $table->dropForeign('servers_owner_foreign');
            $table->dropForeign('servers_allocation_foreign');
            $table->dropForeign('servers_service_foreign');
            $table->dropForeign('servers_option_foreign');

            $table->dropIndex('servers_node_foreign');
            $table->dropIndex('servers_owner_foreign');
            $table->dropIndex('servers_allocation_foreign');
            $table->dropIndex('servers_service_foreign');
            $table->dropIndex('servers_option_foreign');

            $table->dropColumn('deleted_at');
        });

        DB::statement('ALTER TABLE servers
            MODIFY COLUMN node MEDIUMINT(8) UNSIGNED NOT NULL,
            MODIFY COLUMN owner MEDIUMINT(8) UNSIGNED NOT NULL,
            MODIFY COLUMN allocation MEDIUMINT(8) UNSIGNED NOT NULL,
            MODIFY COLUMN service MEDIUMINT(8) UNSIGNED NOT NULL,
            MODIFY COLUMN `option` MEDIUMINT(8) UNSIGNED NOT NULL
        ');
    }
}
