<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBillingInfoToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_address');
            $table->string('billing_city');
            $table->string('billing_country', 2);
            $table->string('billing_zip', 10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('billing_first_name');
            $table->dropColumn('billing_last_name');
            $table->dropColumn('billing_address');
            $table->dropColumn('billing_city');
            $table->dropColumn('billing_country');
            $table->dropColumn('billing_zip');
        });
    }
}
