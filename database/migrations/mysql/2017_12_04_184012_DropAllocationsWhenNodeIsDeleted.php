<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropAllocationsWhenNodeIsDeleted extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['node_id']);

            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['node_id']);

            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }
}
