<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSftpPasswordStorage extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->text('sftp_password')->after('username')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('sftp_password');
        });
    }
}
