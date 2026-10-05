<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier', 48)->unique();
            $table->string('version', 64);
            $table->boolean('enabled')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('extension_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('extension', 48);
            $table->string('key', 191);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['extension', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_settings');
        Schema::dropIfExists('extensions');
    }
};
