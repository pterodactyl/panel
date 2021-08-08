<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetupTableForKeyEncryption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function up()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->char('identifier', 16)->nullable()->unique()->after('user_id');
            $table->dropUnique(['token']);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->text('token')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function down()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('identifier');
            $table->string('token', 32)->unique()->change();
        });
    }
}
