<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->char('uuidShort', 8)->unique();
            $table->mediumInteger('node')->unsigned();
            $table->string('name');
            $table->tinyInteger('active')->unsigned();
            $table->mediumInteger('owner')->unsigned();
            $table->integer('memory')->unsigned();
            $table->integer('swap')->unsigned();
            $table->integer('disk')->unsigned();
            $table->integer('io')->unsigned();
            $table->integer('cpu')->unsigned();
            $table->tinyInteger('oom_disabled')->unsigned()->default(0);
            $table->string('ip');
            $table->integer('port')->unsigned();
            $table->mediumInteger('service')->unsigned();
            $table->mediumInteger('option')->unsigned();
            $table->text('startup');
            $table->char('daemonSecret', 36)->unique();
            $table->string('username')->unique();
            $table->tinyInteger('installed')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('servers');
    }
}
