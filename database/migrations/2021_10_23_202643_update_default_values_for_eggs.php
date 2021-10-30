<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDefaultValuesForEggs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->string('script_container')->default('ghcr.io/pterodactyl/installers:alpine')->after('startup')->change();
            $table->string('script_entry')->default('/bin/ash')->after('copy_script_from')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('eggs', function (Blueprint $table) {
            // You are stuck with the new values because I am too lazy to revert them :)
        });
    }
}
