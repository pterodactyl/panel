<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullableFieldLastrun extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = DB::getQueryGrammar()->wrapTable('tasks');
        DB::statement('ALTER TABLE '.$table.' CHANGE `last_run` `last_run` TIMESTAMP NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = DB::getQueryGrammar()->wrapTable('tasks');
        DB::statement('ALTER TABLE '.$table.' CHANGE `last_run` `last_run` TIMESTAMP;');
    }
}
