<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexForServerAndAction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Doing the index in this order lets me use the action alone without the server
            // or I can later include the server to also filter down at an even more specific
            // level.
            //
            // Ordering the other way around would require a second index for only "action" in
            // order to query a specific action type for any server. Remeber, indexes run left
            // to right in MySQL.
            $table->index(['action', 'server_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['action', 'server_id']);
        });
    }
}
