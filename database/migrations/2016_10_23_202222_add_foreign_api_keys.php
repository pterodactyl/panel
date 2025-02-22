<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignApiKeys extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreign('user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign('api_keys_user_foreign');
            $table->dropIndex('api_keys_user_foreign');
        });
    }
}
