<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateSettingsTableToNewFormat extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('settings')->truncate();
        Schema::table('settings', function (Blueprint $table) {
            $table->increments('id')->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
}
