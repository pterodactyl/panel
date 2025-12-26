<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToEggMountTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the columns having a different type than their relations.
        Schema::table('egg_mount', function (Blueprint $table) {
            $table->unsignedInteger('egg_id')->change();
            $table->unsignedInteger('mount_id')->change();
        });

        // Fetch an array of node and mount ids to check relations against.
        $eggs = DB::table('eggs')->select('id')->pluck('id')->toArray();
        $mounts = DB::table('mounts')->select('id')->pluck('id')->toArray();

        // Drop any relations that are missing an egg or mount.
        DB::table('egg_mount')
            ->select('egg_id', 'mount_id')
            ->whereNotIn('egg_id', $eggs)
            ->orWhereNotIn('mount_id', $mounts)
            ->delete();

        Schema::table('egg_mount', function (Blueprint $table) {
            $table->foreign('egg_id')
                ->references('id')
                ->on('eggs')
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
        Schema::table('egg_mount', function (Blueprint $table) {
            $table->dropForeign(['egg_id']);
            $table->dropForeign(['mount_id']);
        });
    }
}
