<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysServers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->integer('node', false, true)->change();
            $table->integer('owner', false, true)->change();
            $table->integer('allocation', false, true)->change();
            $table->integer('service', false, true)->change();
            $table->integer('option', false, true)->change();

            $table->foreign('node')->references('id')->on('nodes');
            $table->foreign('owner')->references('id')->on('users');
            $table->foreign('allocation')->references('id')->on('allocations');
            $table->foreign('service')->references('id')->on('services');
            $table->foreign('option')->references('id')->on('service_options');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['node']);
            $table->dropIndex(['node']);

            $table->dropForeign(['owner']);
            $table->dropIndex(['owner']);

            $table->dropForeign(['allocation']);
            $table->dropIndex(['allocation']);

            $table->dropForeign(['service']);
            $table->dropIndex(['service']);

            $table->dropForeign(['option']);
            $table->dropIndex(['option']);

            $table->dropColumn('deleted_at');

            $table->mediumInteger('node', false, true)->change();
            $table->mediumInteger('owner', false, true)->change();
            $table->mediumInteger('allocation', false, true)->change();
            $table->mediumInteger('service', false, true)->change();
            $table->mediumInteger('option', false, true)->change();
        });
    }
}
