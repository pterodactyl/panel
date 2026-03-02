<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('installed_worlds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('installed_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('directory');
            $table->unsignedBigInteger('curseforge_project_id');
            $table->unsignedBigInteger('curseforge_file_id');
            $table->string('minecraft_version', 32);
            $table->boolean('is_active')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'directory']);
            $table->index(['server_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_worlds');
    }
};
