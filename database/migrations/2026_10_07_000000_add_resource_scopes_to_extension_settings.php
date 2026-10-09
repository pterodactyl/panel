<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Scopes added beside user_id and server_id, keyed by column prefix. */
    private const array SCOPES = [
        'node' => 'nodes',
        'egg' => 'eggs',
        'location' => 'locations',
        'mount' => 'mounts',
        'database_host' => 'database_hosts',
    ];

    public function up(): void
    {
        Schema::table('extension_settings', function (Blueprint $table): void {
            foreach (self::SCOPES as $scope => $references) {
                $table->unsignedInteger($scope.'_id')->nullable();
                $table->foreign($scope.'_id')->references('id')->on($references)->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        $columns = array_map(fn (string $scope): string => $scope.'_id', array_keys(self::SCOPES));
        throw_if(DB::table('extension_settings')->where(function ($query) use ($columns): void {
            foreach ($columns as $column) {
                $query->orWhereNotNull($column);
            }
        })->exists(), RuntimeException::class, 'Remove extension settings scoped to nodes, eggs, locations, mounts or database hosts before rolling back this migration.');

        Schema::table('extension_settings', function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column) {
                $table->dropForeign([$column]);
            }

            $table->dropColumn($columns);
        });
    }
};
