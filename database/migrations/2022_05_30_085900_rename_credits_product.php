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
        Schema::rename('credit_products', 'shop_products');
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('credit_product_id', 'shop_item_product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('shop_products', 'credit_products');

        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('shop_item_product_id', 'credit_product_id');
        });
    }
};
