<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpgradeTaskSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['server']);

            $table->renameColumn('server', 'server_id');
            $table->unsignedInteger('user_id')->after('id');

            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['server_id', 'user_id']);

            $table->renameColumn('server_id', 'server');
            $table->dropColumn('user_id');

            $table->foreign('server')->references('id')->on('servers');
        });
    }
}
