<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        DB::table('ticket_categories')->insert(
            [
                'name' => 'Technical',
            ]
        );
        DB::table('ticket_categories')->insert(
            [
                'name' => 'Billing',
            ]
        );
        DB::table('ticket_categories')->insert(
            [
                'name' => 'Issue',
            ]
        );
        DB::table('ticket_categories')->insert(
            [
                'name' => 'Request',
            ]
        );
        DB::table('ticket_categories')->insert(
            [
                'name' => 'Other',
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_categories');
    }
};
