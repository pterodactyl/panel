<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCopyScriptFromColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_options', function (Blueprint $table): void {
            $table->unsignedInteger('copy_script_from')->nullable()->after('script_container');

            $table->foreign('copy_script_from')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_options', function (Blueprint $table): void {
            $table->dropForeign(['copy_script_from']);
            $table->dropColumn('copy_script_from');
        });
    }
}
