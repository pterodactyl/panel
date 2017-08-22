<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignDatabaseServers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('database_servers', function (Blueprint $table) {
            $table->foreign('linked_node')->references('id')->on('nodes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('database_servers', function (Blueprint $table) {
            $table->dropForeign('database_servers_linked_node_foreign');
            $table->dropIndex('database_servers_linked_node_foreign');
        });
    }
}
