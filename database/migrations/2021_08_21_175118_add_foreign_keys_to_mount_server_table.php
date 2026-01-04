<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToMountServerTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the columns having a different type than their relations.
        Schema::table('mount_server', function (Blueprint $table) {
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

        Schema::table('mount_server', function (Blueprint $table) {
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
        Schema::table('mount_server', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['mount_id']);
        });
    }
}
