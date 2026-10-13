<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CleanupDatabasesDatabase extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropForeign(['db_server']);

            $table->renameColumn('db_server', 'database_host_id');

            $table->foreign('database_host_id')->references('id')->on('database_hosts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropForeign(['database_host_id']);

            $table->renameColumn('database_host_id', 'db_server');

            $table->foreign('db_server')->references('id')->on('database_hosts');
        });
    }
}
