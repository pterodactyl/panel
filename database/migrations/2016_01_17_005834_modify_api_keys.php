<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyApiKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            DB::statement('ALTER TABLE `api_keys` MODIFY `secret` TINYTEXT NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            DB::statement('ALTER TABLE `api_keys` MODIFY `secret` TINYTEXT NOT NULL');
        });
    }
}
