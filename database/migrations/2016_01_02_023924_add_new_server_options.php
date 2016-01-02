<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewServerOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('oom_disabled')->default(false)->after('cpu');
            $table->mediumInteger('swap')->default(0)->after('memory');
            $table->text('startup')->after('option');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('oom_enabled');
            $table->dropColumn('swap');
            $table->dropColumn('startup');
        });
    }
}
