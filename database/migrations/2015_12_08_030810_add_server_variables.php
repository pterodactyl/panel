<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServerVariables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_variables', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->mediumInteger('server_id')->unsigned();
            $table->mediumInteger('variable_id')->unsigned();
            $table->string('variable_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('server_variables');
    }
}
