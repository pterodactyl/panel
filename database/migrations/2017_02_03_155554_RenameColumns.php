<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameColumns extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['node']);
            $table->dropForeign(['assigned_to']);

            $table->renameColumn('node', 'node_id');
            $table->renameColumn('assigned_to', 'server_id');
            $table->foreign('node_id')->references('id')->on('nodes');
            $table->foreign('server_id')->references('id')->on('servers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropForeign(['server_id']);
            $table->dropIndex(['node_id']);
            $table->dropIndex(['server_id']);

            $table->renameColumn('node_id', 'node');
            $table->renameColumn('server_id', 'assigned_to');
            $table->foreign('node')->references('id')->on('nodes');
            $table->foreign('assigned_to')->references('id')->on('servers');
        });
    }
}
