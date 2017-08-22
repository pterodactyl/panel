<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignAllocations extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE allocations
             MODIFY COLUMN assigned_to INT(10) UNSIGNED NULL,
             MODIFY COLUMN node INT(10) UNSIGNED NOT NULL
         ');

        Schema::table('allocations', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('servers');
            $table->foreign('node')->references('id')->on('nodes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign('allocations_assigned_to_foreign');
            $table->dropForeign('allocations_node_foreign');

            $table->dropIndex('allocations_assigned_to_foreign');
            $table->dropIndex('allocations_node_foreign');
        });

        DB::statement('ALTER TABLE allocations
             MODIFY COLUMN assigned_to MEDIUMINT(8) UNSIGNED NULL,
             MODIFY COLUMN node MEDIUMINT(8) UNSIGNED NOT NULL
         ');
    }
}
