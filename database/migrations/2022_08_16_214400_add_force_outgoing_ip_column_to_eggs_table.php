<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForceOutgoingIpColumnToEggsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->boolean('force_outgoing_ip')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('force_outgoing_ip');
        });
    }
}
