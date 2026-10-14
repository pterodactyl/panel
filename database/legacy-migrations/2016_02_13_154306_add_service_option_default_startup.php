<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceOptionDefaultStartup extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_options', function (Blueprint $table): void {
            $table->text('executable')->after('docker_image')->nullable()->default(null);
            $table->text('startup')->after('executable')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_options', function (Blueprint $table): void {
            $table->dropColumn('executable');
            $table->dropColumn('startup');
        });
    }
}
