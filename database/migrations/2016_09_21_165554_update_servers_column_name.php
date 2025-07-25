<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateServersColumnName extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->renameColumn('server', 'server_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->renameColumn('server_id', 'server');
        });
    }
}
