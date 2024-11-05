<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignApiPermissions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE api_permissions MODIFY key_id INT(10) UNSIGNED NOT NULL');

        Schema::table('api_permissions', function (Blueprint $table) {
            $table->foreign('key_id')->references('id')->on('api_keys');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('api_permissions', function (Blueprint $table) {
            $table->dropForeign('api_permissions_key_id_foreign');
            $table->dropIndex('api_permissions_key_id_foreign');
        });

        DB::statement('ALTER TABLE api_permissions MODIFY key_id MEDIUMINT(8) UNSIGNED NOT NULL');
    }
}
