<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDockerImageToServiceOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('service_options', function (Blueprint $table) {
            $table->renameColumn('docker_tag', 'docker_image');
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->text('docker_image')->change();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('service_options', function (Blueprint $table) {
            $table->renameColumn('docker_image', 'docker_tag');
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->string('docker_tag')->change();
        });

    }
}
