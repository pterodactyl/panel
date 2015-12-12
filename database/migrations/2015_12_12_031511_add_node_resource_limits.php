<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNodeResourceLimits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->mediumInteger('memory_overallocate')->after('memory')->unsigned()->nullable();
            $table->mediumInteger('disk_overallocate')->after('disk')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('memory_overallocate');
            $table->dropColumn('disk_overallocate');
        });
    }
}
