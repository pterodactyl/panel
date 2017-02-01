<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPackColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedInteger('pack')->nullable()->after('option');

            $table->foreign('pack')->references('id')->on('service_packs');
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
            $table->dropForeign('servers_pack_foreign');
            $table->dropIndex('servers_pack_foreign');
            $table->dropColumn('pack');
        });
    }
}
