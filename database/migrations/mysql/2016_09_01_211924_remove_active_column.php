<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveActiveColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->tinyInteger('active')->after('name')->unsigned()->default(0);
        });
    }
}
