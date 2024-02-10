<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewServiceOptionsColumns extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropColumn('executable');

            $table->unsignedInteger('config_from')->nullable()->after('docker_image');
            $table->string('config_stop')->nullable()->after('docker_image');
            $table->text('config_logs')->nullable()->after('docker_image');
            $table->text('config_startup')->nullable()->after('docker_image');
            $table->text('config_files')->nullable()->after('docker_image');

            $table->foreign('config_from')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['config_from']);

            $table->dropColumn('config_from');
            $table->dropColumn('config_stop');
            $table->dropColumn('config_logs');
            $table->dropColumn('config_startup');
            $table->dropColumn('config_files');

            $table->string('executable')->after('docker_image')->nullable();
        });
    }
}
