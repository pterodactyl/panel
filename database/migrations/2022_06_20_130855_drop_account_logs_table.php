<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropAccountLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('account_logs');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('account_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('user_id');
            $table->string('ip_address');
            $table->string('action');

            $table->timestamp('created_at', 0);
            $table->timestamp('updated_at', 0);
        });
    }
}
