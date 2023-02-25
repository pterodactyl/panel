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
        Schema::create('discord_users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id');
            $table->string('username');
            $table->string('avatar')->nullable();
            $table->string('discriminator');
            $table->string('email')->nullable();
            $table->boolean('verified')->nullable();
            $table->integer('public_flags')->nullable();
            $table->integer('flags')->nullable();
            $table->string('locale')->nullable();
            $table->boolean('mfa_enabled')->nullable();
            $table->integer('premium_type')->nullable();
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
        Schema::dropIfExists('discord_users');
    }
};
