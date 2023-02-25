<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBillingPeriodToProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('billing_period')->default("hourly");
            $table->decimal('price', 15, 4)->change();
            $table->decimal('minimum_credits', 15, 4)->change();

        });

        DB::statement('UPDATE products SET billing_period="hourly"');

        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $price = $product->price;
            $price = $price / 30 / 24;
            DB::table('products')->where('id', $product->id)->update(['price' => $price]);
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('billing_period');
            $table->decimal('price', 10, 0)->change();
            $table->float('minimum_credits')->change();
        });
    }
}
