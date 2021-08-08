<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUniqueDatabaseNameToAccountForServer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropUnique(['database_host_id', 'database']);
        });

        Schema::table('databases', function (Blueprint $table) {
            $table->unique(['database_host_id', 'server_id', 'database']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropUnique(['database_host_id', 'server_id', 'database']);
        });

        Schema::table('databases', function (Blueprint $table) {
            $table->unique(['database_host_id', 'database']);
        });
    }
}
