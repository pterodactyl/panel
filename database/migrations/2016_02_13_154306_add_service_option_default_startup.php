<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceOptionDefaultStartup extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->text('executable')->after('docker_image')->nullable()->default(null);
            $table->text('startup')->after('executable')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropColumn('executable');
            $table->dropColumn('startup');
        });
    }
}
