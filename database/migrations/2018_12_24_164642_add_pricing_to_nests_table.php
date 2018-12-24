<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPricingToNestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nests', function (Blueprint $table) {
            $table->integer('database_limit')->default(0);
            $table->integer('allocation_limit')->default(1);
            $table->float('memory_monthly_cost')->default(1);
            $table->float('disk_monthly_cost')->default(.10);
            $table->integer('cpu_limit')->default(0);
            $table->float('max_memory')->default(4);
            $table->float('max_disk')->default(25);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nests', function (Blueprint $table) {
            $table->dropColumn('database_limit');
            $table->dropColumn('allocation_limit');
            $table->dropColumn('memory_monthly_cost');
            $table->dropColumn('disk_monthly_cost');
            $table->dropColumn('cpu_limit');
            $table->dropColumn('max_memory');
            $table->dropColumn('max_disk');
        });
    }
}
