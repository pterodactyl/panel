<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AllowEggVariablesToHaveLongerValues extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('egg_variables', function (Blueprint $table) {
            $table->text('default_value')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('egg_variables', function (Blueprint $table) {
            $table->string('default_value')->change();
        });
    }
}
