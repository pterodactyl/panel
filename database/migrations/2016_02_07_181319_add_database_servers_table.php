<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDatabaseServersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('database_servers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('host');
            $table->integer('port')->unsigned();
            $table->string('username');
            $table->text('password');
            $table->integer('max_databases')->unsigned()->nullable();
            $table->integer('linked_node')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('database_servers');
    }
}
