<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPacksFromServers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['pack_id']);
            $table->dropColumn('pack_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedInteger('pack_id')->after('egg_id')->nullable();
            $table->foreign('pack_id')->references('id')->on('packs');
        });
    }
}
