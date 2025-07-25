<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeAllocationFieldsJson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->json('old_additional_allocations')->nullable()->change();
            $table->json('new_additional_allocations')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->string('old_additional_allocations')->nullable()->change();
            $table->string('new_additional_allocations')->nullable()->change();
        });
    }
}
