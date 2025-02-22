<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceOptions extends Migration
{
    /**
     * Run the migrations.
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
     */
    public function down()
    {
        Schema::dropIfExists('service_options');
    }
}
