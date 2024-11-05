<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('packs');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('egg_id');
            $table->char('uuid', 36)->unique();
            $table->string('name');
            $table->string('version');
            $table->text('description')->nullable();
            $table->tinyInteger('selectable')->default(1);
            $table->tinyInteger('visible')->default(1);
            $table->tinyInteger('locked')->default(0);
            $table->timestamps();
        });

        Schema::table('packs', function (Blueprint $table) {
            $table->foreign('egg_id')->references('id')->on('eggs')->cascadeOnDelete();
        });
    }
}
