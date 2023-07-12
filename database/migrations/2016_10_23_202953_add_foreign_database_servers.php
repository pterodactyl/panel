<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignDatabaseServers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('database_servers', function (Blueprint $table) {
            $table->foreign('linked_node')->references('id')->on('nodes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('database_servers', function (Blueprint $table) {
            $table->dropForeign(['linked_node']);
            $table->dropIndex(['linked_node']);
        });
    }
}
