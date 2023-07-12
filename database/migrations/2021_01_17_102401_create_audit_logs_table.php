<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('server_id')->nullable();
            $table->string('action');
            $table->string('subaction')->nullable();
            $table->json('device');
            $table->json('metadata');
            $table->timestamp('created_at', 0);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
}
