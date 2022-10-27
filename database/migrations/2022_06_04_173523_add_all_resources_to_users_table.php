<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAllResourcesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('store_slot');

            $table->unsignedInteger('store_slots');
            $table->unsignedInteger('store_ports');
            $table->unsignedInteger('store_backups');
            $table->unsignedInteger('store_databases');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('store_slot');

            $table->dropColumn('store_slots');
            $table->dropColumn('store_ports');
            $table->dropColumn('store_backups');
            $table->dropColumn('store_databases');
        });
    }
}
