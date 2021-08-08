<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApiKeyPermissionColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('api_permissions');

        Schema::table('api_keys', function (Blueprint $table) {
            $table->unsignedTinyInteger('r_servers')->default(0);
            $table->unsignedTinyInteger('r_nodes')->default(0);
            $table->unsignedTinyInteger('r_allocations')->default(0);
            $table->unsignedTinyInteger('r_users')->default(0);
            $table->unsignedTinyInteger('r_locations')->default(0);
            $table->unsignedTinyInteger('r_nests')->default(0);
            $table->unsignedTinyInteger('r_eggs')->default(0);
            $table->unsignedTinyInteger('r_database_hosts')->default(0);
            $table->unsignedTinyInteger('r_server_databases')->default(0);
            $table->unsignedTinyInteger('r_packs')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('api_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('key_id');
            $table->string('permission');

            $table->foreign('key_id')->references('id')->on('api_keys')->onDelete('cascade');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn([
                'r_servers',
                'r_nodes',
                'r_allocations',
                'r_users',
                'r_locations',
                'r_nests',
                'r_eggs',
                'r_database_hosts',
                'r_server_databases',
                'r_packs',
            ]);
        });
    }
}
