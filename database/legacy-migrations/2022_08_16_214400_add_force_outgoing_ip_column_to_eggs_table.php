<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForceOutgoingIpColumnToEggsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table): void {
            $table->boolean('force_outgoing_ip')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table): void {
            $table->dropColumn('force_outgoing_ip');
        });
    }
}
