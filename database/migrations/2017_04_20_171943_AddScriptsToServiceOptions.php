<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScriptsToServiceOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->text('script_install')->after('startup')->nullable();
            $table->boolean('script_is_privileged')->default(true)->after('startup');
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
            $table->dropColumn('script_install');
            $table->dropColumn('script_is_privileged');
        });
    }
}
