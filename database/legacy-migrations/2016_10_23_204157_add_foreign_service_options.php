<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignServiceOptions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_options', function (Blueprint $table): void {
            $table->integer('parent_service', false, true)->change();
            $table->foreign('parent_service')->references('id')->on('services');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_options', function (Blueprint $table): void {
            $table->dropForeign(['parent_service']);
            $table->dropIndex(['parent_service']);

            $table->mediumInteger('parent_service', false, true)->change();
        });
    }
}
