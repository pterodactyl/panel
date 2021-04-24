<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApiPermissions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('api_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumInteger('key_id')->unsigned();
            $table->string('permission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('api_permissions');
    }
}
