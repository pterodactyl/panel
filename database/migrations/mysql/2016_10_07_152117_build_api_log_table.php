<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BuildApiLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('authorized');
            $table->text('error')->nullable();
            $table->char('key', 16)->nullable();
            $table->char('method', 6);
            $table->text('route');
            $table->text('content')->nullable();
            $table->text('user_agent');
            $table->ipAddress('request_ip');
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('api_logs');
    }
}
