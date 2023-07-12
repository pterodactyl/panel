<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateApiKeys extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->unsignedInteger('user')->after('id');
            $table->text('memo')->after('allowed_ips')->nullable();
            $table->timestamp('expires_at')->after('memo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('user');
            $table->dropColumn('memo');
            $table->dropColumn('expires_at');
        });
    }
}
