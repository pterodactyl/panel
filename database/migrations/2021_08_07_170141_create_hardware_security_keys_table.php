<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHardwareSecurityKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hardware_security_keys', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36);
            $table->unsignedInteger('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('public_key_id');
            $table->text('public_key');
            $table->char('aaguid', 36);
            $table->string('type');
            $table->json('transports');
            $table->string('attestation_type');
            $table->json('trust_path');
            $table->text('user_handle');
            $table->unsignedInteger('counter');
            $table->json('other_ui');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hardware_security_keys');
    }
}
