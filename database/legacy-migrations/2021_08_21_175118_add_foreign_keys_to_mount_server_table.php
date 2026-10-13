<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToMountServerTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the columns having a different type than their relations.
        Schema::table('mount_server', function (Blueprint $table): void {
            $table->unsignedInteger('server_id')->change();
            $table->unsignedInteger('mount_id')->change();
        });

        // Fetch an array of node and mount ids to check relations against.
        $servers = DB::table('servers')->select('id')->pluck('id')->toArray();
        $mounts = DB::table('mounts')->select('id')->pluck('id')->toArray();

        // Drop any relations that are missing a server or mount.
        DB::table('mount_server')
            ->select('server_id', 'mount_id')
            ->whereNotIn('server_id', $servers)
            ->orWhereNotIn('mount_id', $mounts)
            ->delete();

        Schema::table('mount_server', function (Blueprint $table): void {
            $table->foreign('server_id')
                ->references('id')
                ->on('servers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('mount_id')->references('id')
                ->on('mounts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mount_server', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['mount_id']);
        });
    }
}
