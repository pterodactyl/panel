<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateServersColumnName extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->renameColumn('server', 'server_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->renameColumn('server_id', 'server');
        });
    }
}
