<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUserPermissionsToDeleteOnUserDeletion extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['subuser_id']);

            $table->foreign('subuser_id')->references('id')->on('subusers')->onDelete('cascade');
        });

        Schema::table('subusers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['server_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subusers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['server_id']);

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('server_id')->references('id')->on('servers');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['subuser_id']);

            $table->foreign('subuser_id')->references('id')->on('subusers');
        });
    }
}
