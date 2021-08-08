<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AllowNegativeValuesForOverallocation extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('disk_overallocate')->default(0)->nullable(false)->change();
            $table->integer('memory_overallocate')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            DB::statement('ALTER TABLE nodes MODIFY disk_overallocate MEDIUMINT UNSIGNED NULL, 
                                             MODIFY memory_overallocate MEDIUMINT UNSIGNED NULL');
        });
    }
}
