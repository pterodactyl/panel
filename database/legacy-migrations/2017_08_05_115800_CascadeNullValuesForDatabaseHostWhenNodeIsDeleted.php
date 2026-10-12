<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CascadeNullValuesForDatabaseHostWhenNodeIsDeleted extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('database_hosts', function (Blueprint $table): void {
            $table->dropForeign(['node_id']);
            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('database_hosts', function (Blueprint $table): void {
            $table->dropForeign(['node_id']);
            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }
}
