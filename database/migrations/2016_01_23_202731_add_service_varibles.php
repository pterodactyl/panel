<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceVaribles extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_variables', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumInteger('option_id')->unsigned();
            $table->string('name');
            $table->text('description');
            $table->string('env_variable');
            $table->string('default_value');
            $table->tinyInteger('user_viewable')->unsigned();
            $table->tinyInteger('user_editable')->unsigned();
            $table->tinyInteger('required')->unsigned();
            $table->string('regex')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_variables');
    }
}
