<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateJobsTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_reserved_reserved_at_index');
            $table->dropColumn('reserved');
            $table->index(['queue', 'reserved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_reserved_at_index');
            $table->tinyInteger('reserved')->unsigned();
            $table->index(['queue', 'reserved', 'reserved_at']);
        });
    }
}
