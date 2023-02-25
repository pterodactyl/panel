<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign('servers_egg_id_foreign');
            $table->dropForeign('servers_location_id_foreign');
            $table->dropColumn('egg_id');
            $table->dropColumn('location_id');
            $table->string('config')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->foreignId('egg_id')->references('id')->on('eggs');
            $table->foreignId('location_id')->references('id')->on('locations');
            $table->dropColumn('config');
        });
    }
};
