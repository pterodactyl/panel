<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChecksumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checksums', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service')->unsigned();
            $table->string('filename');
            $table->char('checksum', 40);
            $table->timestamps();

            $table->foreign('service')->references('id')->on('services');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('checksums');
    }
}
