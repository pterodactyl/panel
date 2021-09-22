<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSslcertSslkeyColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
		Schema::table('nodes', function (Blueprint $table) {
			$table->string('sslcert')->after('scheme');
			
			$table->string('sslkey')->after('sslcert');
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
		Schema::table('nodes', function (Blueprint $table) {
			$table->dropColumn('sslcert');
			
			$table->dropColumn('sslkey');
		});
    }
}