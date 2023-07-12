<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropDaemonKeyTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('daemon_keys');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('daemon_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('user_id');
            $table->string('secret')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::table('daemon_keys', function (Blueprint $table) {
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
}
