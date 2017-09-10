<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullableNextRunColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table = DB::getQueryGrammar()->wrapTable('tasks');
            DB::statement('ALTER TABLE ' . $table . ' CHANGE `next_run` `next_run` TIMESTAMP NULL;');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table = DB::getQueryGrammar()->wrapTable('tasks');
            DB::statement('ALTER TABLE ' . $table . ' CHANGE `next_run` `next_run` TIMESTAMP NOT NULL;');
        });
    }
}
