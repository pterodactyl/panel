<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeSuccessfulNullableInServerTransfers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->boolean('successful')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_transfers', function (Blueprint $table) {
            $table->boolean('successful')->default(0)->change();
        });
    }
}
