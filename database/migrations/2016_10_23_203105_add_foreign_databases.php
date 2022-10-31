<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignDatabases extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('db_server')->references('id')->on('database_servers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            $table->dropIndex(['server_id']);

            $table->dropForeign(['db_server']);
            $table->dropIndex(['db_server']);
        });
    }
}
