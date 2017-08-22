<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CascadeNullValuesForDatabaseHostWhenNodeIsDeleted extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }
}
