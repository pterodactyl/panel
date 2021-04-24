<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamePermissionsColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->renameColumn('permissions', 'permission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
        });
    }
}
