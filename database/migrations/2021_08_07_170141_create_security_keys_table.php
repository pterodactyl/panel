<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSecurityKeysTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_keys', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->text('public_key_id');
            $table->text('public_key');
            $table->char('aaguid', 36)->nullable();
            $table->string('type');
            $table->json('transports');
            $table->string('attestation_type');
            $table->json('trust_path');
            $table->text('user_handle');
            $table->unsignedInteger('counter');
            $table->json('other_ui')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_keys');
    }
}
