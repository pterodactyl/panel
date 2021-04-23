<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SupportMultipleDockerImagesAndUpdates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->json('docker_images')->after('docker_image')->nullable();
            $table->text('update_url')->after('docker_images')->nullable();
        });

        Schema::table('eggs', function (Blueprint $table) {
            DB::statement('UPDATE `eggs` SET `docker_images` = JSON_ARRAY(docker_image)');
        });

        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('docker_image');
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
            $table->text('docker_image')->after('docker_images');
        });

        Schema::table('eggs', function (Blueprint $table) {
            DB::statement('UPDATE `eggs` SET `docker_image` = JSON_UNQUOTE(JSON_EXTRACT(docker_images, "$[0]"))');
        });

        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('docker_images');
            $table->dropColumn('update_url');
        });
    }
}
