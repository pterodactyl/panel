<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiKeyPermissionColumns extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('api_permissions');

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->unsignedTinyInteger('r_servers')->default(0);
            $table->unsignedTinyInteger('r_nodes')->default(0);
            $table->unsignedTinyInteger('r_allocations')->default(0);
            $table->unsignedTinyInteger('r_users')->default(0);
            $table->unsignedTinyInteger('r_locations')->default(0);
            $table->unsignedTinyInteger('r_nests')->default(0);
            $table->unsignedTinyInteger('r_eggs')->default(0);
            $table->unsignedTinyInteger('r_database_hosts')->default(0);
            $table->unsignedTinyInteger('r_server_databases')->default(0);
            $table->unsignedTinyInteger('r_packs')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('api_permissions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('key_id');
            $table->string('permission');

            $table->foreign('key_id')->references('id')->on('api_keys')->onDelete('cascade');
        });

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->dropColumn([
                'r_servers',
                'r_nodes',
                'r_allocations',
                'r_users',
                'r_locations',
                'r_nests',
                'r_eggs',
                'r_database_hosts',
                'r_server_databases',
                'r_packs',
            ]);
        });
    }
}
