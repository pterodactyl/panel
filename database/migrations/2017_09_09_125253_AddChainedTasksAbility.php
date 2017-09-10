<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChainedTasksAbility extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('parent_task_id')->after('id')->nullable();
            $table->unsignedInteger('chain_order')->after('parent_task_id')->nullable();
            $table->unsignedInteger('chain_delay')->after('minute')->nullable();
            $table->string('name')->after('server_id')->nullable();

            $table->foreign('parent_task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->index(['parent_task_id', 'chain_order']);

            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('year');
            $table->dropColumn('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['parent_task_id']);
            $table->dropIndex(['parent_task_id', 'chain_order']);
            $table->dropColumn('parent_task_id');
            $table->dropColumn('chain_order');
            $table->dropColumn('chain_delay');
            $table->dropColumn('name');

            $table->unsignedInteger('user_id')->after('id')->nullable();
            $table->string('year')->after('queued')->default('*');
            $table->string('month')->after('year')->default('*');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}
