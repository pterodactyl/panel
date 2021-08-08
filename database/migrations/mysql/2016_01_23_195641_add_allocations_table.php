<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumInteger('node')->unsigned();
            $table->string('ip');
            $table->mediumInteger('port')->unsigned();
            $table->mediumInteger('assigned_to')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('allocations');
    }
}
