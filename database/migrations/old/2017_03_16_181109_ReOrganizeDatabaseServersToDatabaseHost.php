<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReOrganizeDatabaseServersToDatabaseHost extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('database_servers', function (Blueprint $table): void {
            $table->dropForeign(['linked_node']);
        });

        Schema::rename('database_servers', 'database_hosts');

        Schema::table('database_hosts', function (Blueprint $table): void {
            $table->renameColumn('linked_node', 'node_id');

            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('database_hosts', function (Blueprint $table): void {
            $table->dropForeign(['node_id']);
        });

        Schema::rename('database_hosts', 'database_servers');

        Schema::table('database_servers', function (Blueprint $table): void {
            $table->renameColumn('node_id', 'linked_node');

            $table->foreign('linked_node')->references('id')->on('nodes');
        });
    }
}
