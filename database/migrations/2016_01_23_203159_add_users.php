<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->string('email')->unique();
            $table->text('password');
            $table->string('remember_token')->nullable();
            $table->char('language', 5)->default('en');
            $table->tinyInteger('root_admin')->unsigned()->default(0);
            $table->tinyInteger('use_totp')->unsigned();
            $table->char('totp_secret', 16)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
