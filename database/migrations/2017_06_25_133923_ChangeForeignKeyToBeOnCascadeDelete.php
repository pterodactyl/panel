<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeForeignKeyToBeOnCascadeDelete extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('api_permissions', function (Blueprint $table) {
            $table->dropForeign(['key_id']);

            $table->foreign('key_id')->references('id')->on('api_keys')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('api_permissions', function (Blueprint $table) {
            $table->dropForeign(['key_id']);

            $table->foreign('key_id')->references('id')->on('api_keys');
        });
    }
}
