<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInstallAndUpgradePaths extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->text('script_upgrade')->after('startup')->nullable();
            $table->text('script_install')->after('startup')->nullable();
            $table->boolean('script_is_privileged')->default(false)->after('startup');
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
            $table->dropColumn('script_upgrade');
            $table->dropColumn('script_install');
            $table->dropColumn('script_is_privileged');
        });
    }
}
