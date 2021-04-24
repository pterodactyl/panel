<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDaemonKeysTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('daemon_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('user_id');
            $table->string('secret')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['server_id', 'user_id']);
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('daemon_keys');
    }
}
