<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->boolean('enabled')->default(true)->after('node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });
    }
};
