<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_options', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumInteger('parent_service')->unsigned();
            $table->string('name');
            $table->text('description');
            $table->string('tag');
            $table->text('docker_image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExsits('service_options');
    }
}
