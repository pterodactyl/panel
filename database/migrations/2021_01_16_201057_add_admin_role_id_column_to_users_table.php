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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('admin_role_id')->nullable()->unsigned()->after('language');
            $table->index('admin_role_id');
            $table->foreign('admin_role_id')->references('id')->on('admin_roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['admin_role_id']);
            $table->dropColumn('admin_role_id');
        });
    }
};
