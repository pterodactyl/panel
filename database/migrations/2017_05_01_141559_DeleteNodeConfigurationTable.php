<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteNodeConfigurationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('node_configuration_tokens');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::create('node_configuration_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->char('token', 32);
            $table->unsignedInteger('node_id');
            $table->timestamps();
        });

        Schema::table('node_configuration_tokens', function (Blueprint $table) {
            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }
}
