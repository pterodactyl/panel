<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexForServerAndAction extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            // Doing the index in this order lets me use the action alone without the server
            // or I can later include the server to also filter down at an even more specific
            // level.
            //
            // Ordering the other way around would require a second index for only "action" in
            // order to query a specific action type for any server. Remeber, indexes run left
            // to right in MySQL.
            $table->index(['action', 'server_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['action', 'server_id']);
        });
    }
}
