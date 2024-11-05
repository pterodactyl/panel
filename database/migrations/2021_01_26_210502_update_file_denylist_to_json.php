<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFileDenylistToJson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('file_denylist');
        });

        Schema::table('eggs', function (Blueprint $table) {
            $table->json('file_denylist')->nullable()->after('docker_images');
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
            $table->dropColumn('file_denylist');
        });

        Schema::table('eggs', function (Blueprint $table) {
            $table->text('file_denylist')->after('docker_images');
        });
    }
}
