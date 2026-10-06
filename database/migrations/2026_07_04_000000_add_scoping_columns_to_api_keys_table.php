<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Adds optional scoping to client API keys. Both columns are nullable and
     * a NULL value means "no restriction", which keeps every existing API key
     * working exactly as it did before this migration.
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('allowed_ips');
            $table->json('allowed_servers')->nullable()->after('permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn(['permissions', 'allowed_servers']);
        });
    }
};
