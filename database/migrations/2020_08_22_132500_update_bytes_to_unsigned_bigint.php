<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBytesToUnsignedBigint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->unsignedBigInteger('bytes')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->integer('bytes')->default(0)->change();
        });
    }
}
