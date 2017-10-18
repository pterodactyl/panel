<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignNodes extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE nodes MODIFY location INT(10) UNSIGNED NOT NULL');

        Schema::table('nodes', function (Blueprint $table) {
            $table->foreign('location')->references('id')->on('locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign('nodes_location_foreign');
            $table->dropIndex('nodes_location_foreign');
        });

        DB::statement('ALTER TABLE nodes MODIFY location MEDIUMINT(10) UNSIGNED NOT NULL');
    }
}
