<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveDaemonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('daemon');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('daemon', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->mediumInteger('server')->unsigned();
            $table->string('parameter');
            $table->text('value');
            $table->tinyInteger('editable')->unsigned()->default(0);
            $table->tinyInteger('visible')->unsigned()->default(0);
            $table->text('regex')->nullable();
            $table->timestamps();
        });
    }
}
