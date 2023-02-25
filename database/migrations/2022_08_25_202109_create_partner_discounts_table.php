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
        Schema::create('partner_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->integer('partner_discount');
            $table->integer('registered_user_discount');
            $table->integer('referral_system_commission');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partner_discounts');
    }
};
