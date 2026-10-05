<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignDatabases extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('db_server')->references('id')->on('database_servers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
            $table->dropIndex(['server_id']);

            $table->dropForeign(['db_server']);
            $table->dropIndex(['db_server']);
        });
    }
}
