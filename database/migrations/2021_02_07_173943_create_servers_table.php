<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->dateTime('suspended')->nullable();
            $table->string('identifier')->nullable();
            $table->integer('pterodactyl_id')->nullable();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('egg_id')->references('id')->on('eggs');
            $table->foreignId('location_id')->references('id')->on('locations');
            $table->foreignUuid('product_id')->references('id')->on('products');
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
        Schema::dropIfExists('servers');
    }
};
