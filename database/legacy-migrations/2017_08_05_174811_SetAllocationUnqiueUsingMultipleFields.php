<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetAllocationUnqiueUsingMultipleFields extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('allocations', function (Blueprint $table): void {
            $table->unique(['node_id', 'ip', 'port']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table): void {
            $table->dropForeign(['node_id']);
            $table->dropUnique(['node_id', 'ip', 'port']);
            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }
}
