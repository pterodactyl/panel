<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('category_id');
            $table->text('image_url');
            $table->string('short_url');
            $table->integer('egg_id');
            $table->integer('cpu');
            $table->integer('memory');
            $table->integer('swap');
            $table->integer('disk');
            $table->integer('database_limit');
            $table->integer('backup_limit');
            $table->integer('allocation_limit');
            $table->text('node_ids');
            $table->decimal('price');
            $table->integer('hide');
            $table->integer('sort');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
}
