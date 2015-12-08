<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceOptionVariables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_variables', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->mediumInteger('option_id')->unsigned();
            $table->string('name');
            $table->text('description');
            $table->string('env_variable');
            $table->string('default_value');
            $table->boolean('user_viewable');
            $table->boolean('user_editable');
            $table->boolean('required');
            $table->string('regex');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('service_variables');
    }
}
