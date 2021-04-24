<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyToPacks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->foreign('pack_id')->references('id')->on('packs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['pack_id']);
        });
    }
}
