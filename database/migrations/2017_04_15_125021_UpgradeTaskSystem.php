<?php

use Pterodactyl\Models\Task;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpgradeTaskSystem extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['server']);

            $table->renameColumn('server', 'server_id');
            $table->unsignedInteger('user_id')->nullable()->after('id');

            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('user_id')->references('id')->on('users');
        });

        DB::transaction(function () {
            foreach (Task::all() as $task) {
                $task->user_id = $task->server->owner_id;
                $task->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
//            $table->dropForeign(['server_id']);
//            $table->dropForeign(['user_id']);

            $table->renameColumn('server_id', 'server');
            $table->dropColumn('user_id');

            $table->foreign('server')->references('id')->on('servers');
        });
    }
}
