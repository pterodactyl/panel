<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteServiceExecutableOption extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('file', 'folder');
            $table->dropColumn('executable');
            $table->text('description')->nullable()->change();
            $table->text('startup')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('executable')->after('folder');
            $table->renameColumn('folder', 'file');
            $table->text('description')->nullable(false)->change();
            $table->text('startup')->nullable(false)->change();
        });
    }
}
