<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToMountNodeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the columns having a different type than their relations.
        Schema::table('mount_node', function (Blueprint $table) {
            $table->unsignedInteger('node_id')->change();
            $table->unsignedInteger('mount_id')->change();
        });

        // Fetch an array of node and mount ids to check relations against.
        $nodes = DB::table('nodes')->select('id')->pluck('id')->toArray();
        $mounts = DB::table('mounts')->select('id')->pluck('id')->toArray();

        // Drop any relations that are missing a node or mount.
        DB::table('mount_node')
            ->select('node_id', 'mount_id')
            ->whereNotIn('node_id', $nodes)
            ->orWhereNotIn('mount_id', $mounts)
            ->delete();

        Schema::table('mount_node', function (Blueprint $table) {
            $table->foreign('node_id')
                ->references('id')
                ->on('nodes')
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
        Schema::table('mount_node', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropForeign(['mount_id']);
        });
    }
}
