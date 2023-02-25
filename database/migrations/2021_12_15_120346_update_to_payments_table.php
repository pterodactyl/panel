<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method');
            $table->dropColumn('payer');
            $table->dropColumn('payer_id');
            $table->string('credit_product_id');
        });

        DB::statement('UPDATE payments SET payment_method="paypal"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->string('payer_id')->nullable();
            $table->text('payer')->nullable();
            $table->dropColumn('credit_product_id');
        });
    }
};
