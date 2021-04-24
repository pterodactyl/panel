<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTasksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server')->unsigned();
            $table->tinyInteger('active')->default(1);
            $table->string('action');
            $table->text('data');
            $table->tinyInteger('queued')->unsigned()->default(0);
            $table->string('year')->default('*');
            $table->string('day_of_week')->default('*');
            $table->string('month')->default('*');
            $table->string('day_of_month')->default('*');
            $table->string('hour')->default('*');
            $table->string('minute')->default('*');
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('tasks');
    }
}
