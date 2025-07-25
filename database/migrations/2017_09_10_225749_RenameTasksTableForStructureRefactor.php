<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class RenameTasksTableForStructureRefactor extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::rename('tasks', 'tasks_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::rename('tasks_old', 'tasks');
    }
}
