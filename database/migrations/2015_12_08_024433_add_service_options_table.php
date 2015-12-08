<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_options', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->mediumInteger('parent_service')->unsigned();
            $table->string('name');
            $table->text('description');
            $table->string('config_file');
            $table->binary('config_blob')->nullable();
            $table->string('docker_tag');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('service_options');
    }
}
