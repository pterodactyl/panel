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
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('tax_value', 8, 2)->after('price')->nullable();
            $table->integer('tax_percent')->after('tax_value')->nullable();
            $table->decimal('total_price', 8, 2)->after('tax_percent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('tax_value');
            $table->dropColumn('tax_percent');
            $table->dropColumn('total_price');
        });
    }
};
