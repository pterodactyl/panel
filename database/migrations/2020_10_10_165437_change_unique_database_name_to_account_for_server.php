<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUniqueDatabaseNameToAccountForServer extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropUnique(['database_host_id', 'database']);
        });

        Schema::table('databases', function (Blueprint $table) {
            $table->unique(['database_host_id', 'server_id', 'database']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropUnique(['database_host_id', 'server_id', 'database']);
        });

        Schema::table('databases', function (Blueprint $table) {
            $table->unique(['database_host_id', 'database']);
        });
    }
}
