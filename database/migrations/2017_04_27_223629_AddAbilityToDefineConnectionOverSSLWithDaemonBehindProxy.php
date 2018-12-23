<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAbilityToDefineConnectionOverSSLWithDaemonBehindProxy extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->boolean('behind_proxy')->after('scheme')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('behind_proxy');
        });
    }
}
