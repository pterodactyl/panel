<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SupportMultipleDockerImagesAndUpdates extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->json('docker_images')->after('docker_image')->nullable();
            $table->text('update_url')->after('docker_images')->nullable();
        });

        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::table('eggs')->update(['docker_images' => DB::raw('JSON_ARRAY(docker_image)')]);
                break;
            case 'pgsql':
                DB::table('eggs')->update(['docker_images' => DB::raw('jsonb_build_array(docker_image)')]);
                break;
        }

        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('docker_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->text('docker_image')->after('docker_images');
        });

        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::table('eggs')->update(['docker_images' => DB::raw('JSON_UNQUOTE(JSON_EXTRACT(docker_images, "$[0]")')]);
                break;
            case 'pgsql':
                DB::table('eggs')->update(['docker_images' => DB::raw('JSON_UNQUOTE(JSON_EXTRACT(docker_images, "$[0]")')]);
                DB::table('eggs')->update(['docker_images' => DB::raw('docker_images->>0')]);
                break;
        }

        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('docker_images');
            $table->dropColumn('update_url');
        });
    }
}
