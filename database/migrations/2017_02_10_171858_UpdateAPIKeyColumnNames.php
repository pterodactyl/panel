<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAPIKeyColumnNames extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign('api_keys_user_foreign')->dropIndex('api_keys_user_foreign');

            $table->renameColumn('user', 'user_id');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign('api_keys_user_id_foreign')->dropIndex('api_keys_user_id_foreign');

            $table->renameColumn('user_id', 'user');
            $table->foreign('user')->references('id')->on('users');
        });
    }
}
