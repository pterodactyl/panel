<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScriptsToServiceOptions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->text('script_install')->after('startup')->nullable();
            $table->boolean('script_is_privileged')->default(true)->after('startup');
            $table->string('script_entry')->default('ash')->after('startup');
            $table->string('script_container')->default('alpine:3.4')->after('startup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropColumn('script_install');
            $table->dropColumn('script_is_privileged');
            $table->dropColumn('script_entry');
            $table->dropColumn('script_container');
        });
    }
}
