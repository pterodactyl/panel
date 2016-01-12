<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixNodeSchemeField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nodes', function (Blueprint $table) {
            DB::statement('ALTER TABLE `nodes` MODIFY `scheme` VARCHAR(5) DEFAULT \'https\' NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            DB::statement('ALTER TABLE `nodes` MODIFY `scheme` BOOLEAN() DEFAULT 0 NOT NULL');
        });
    }
}
