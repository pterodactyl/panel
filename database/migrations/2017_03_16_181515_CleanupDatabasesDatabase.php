<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CleanupDatabasesDatabase extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropForeign(['db_server']);

            $table->renameColumn('db_server', 'database_host_id');

            $table->foreign('database_host_id')->references('id')->on('database_hosts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropForeign(['database_host_id']);

            $table->renameColumn('database_host_id', 'db_server');

            $table->foreign('db_server')->references('id')->on('database_hosts');
        });
    }
}
