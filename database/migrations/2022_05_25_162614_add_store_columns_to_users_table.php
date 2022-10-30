<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStoreColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('store_balance');
            $table->unsignedInteger('store_slot');
            $table->unsignedInteger('store_cpu');
            $table->unsignedInteger('store_memory');
            $table->unsignedInteger('store_disk');
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
            $table->dropColumn('store_balance');
            $table->dropColumn('store_slot');
            $table->dropColumn('store_cpu');
            $table->dropColumn('store_memory');
            $table->dropColumn('store_disk');
        });
    }
}
