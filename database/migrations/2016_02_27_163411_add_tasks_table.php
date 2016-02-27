<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server')->unsigned();
            $table->string('action');
            $table->text('data');
            $table->tinyInteger('queued')->unsigned()->default(0);
            $table->integer('month')->default(0);
            $table->integer('week')->default(0);
            $table->integer('day')->default(0);
            $table->integer('hour')->default(0);
            $table->integer('minute')->default(0);
            $table->integer('second')->default(0);
            $table->timestamp('last_run');
            $table->timestamp('next_run');
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
        Schema::drop('tasks');
    }
}
