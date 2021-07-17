<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebauthn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webauthn_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');

            $table->string('name')->default('key');
            $table->string('credential_id', 255);
            $table->string('type', 255);
            $table->text('transports');
            $table->string('attestation_type', 255);
            $table->text('trust_path');
            $table->text('aaguid');
            $table->text('credential_public_key');
            $table->integer('counter');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('credential_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webauthn_keys');
    }
}
