<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mounts', function (Blueprint $table) {
            $table->increments('id')->unique();
            $table->char('uuid', 36)->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('source');
            $table->string('target');
            $table->tinyInteger('read_only')->unsigned();
            $table->tinyInteger('user_mountable')->unsigned();
        });

        Schema::create('egg_mount', function (Blueprint $table) {
            $table->integer('egg_id');
            $table->integer('mount_id');

            $table->unique(['egg_id', 'mount_id']);
        });

        Schema::create('mount_node', function (Blueprint $table) {
            $table->integer('node_id');
            $table->integer('mount_id');

            $table->unique(['node_id', 'mount_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mount_node');
        Schema::dropIfExists('egg_mount');
        Schema::dropIfExists('mounts');
    }
}
