<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableServerTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Nuclear approach to whatever plugins are out there and not properly namespacing their own tables
        // leading to constant support requests from people...
        Schema::dropIfExists('server_transfers');

        Schema::create('server_transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server_id')->unsigned();
            $table->tinyInteger('successful')->unsigned()->default(0);
            $table->integer('old_node')->unsigned();
            $table->integer('new_node')->unsigned();
            $table->integer('old_allocation')->unsigned();
            $table->integer('new_allocation')->unsigned();
            $table->string('old_additional_allocations')->nullable();
            $table->string('new_additional_allocations')->nullable();
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('server_transfers');
    }
}
