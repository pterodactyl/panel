<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUniqueDatabaseNameToAccountForServer extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropUnique(['database_host_id', 'database']);
        });

        Schema::table('databases', function (Blueprint $table): void {
            $table->unique(['database_host_id', 'server_id', 'database']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropUnique(['database_host_id', 'server_id', 'database']);
        });

        Schema::table('databases', function (Blueprint $table): void {
            $table->unique(['database_host_id', 'database']);
        });
    }
}
