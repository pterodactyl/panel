<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNodes extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('public')->unsigned();
            $table->string('name');
            $table->mediumInteger('location')->unsigned();
            $table->string('fqdn');
            $table->string('scheme')->default('https');
            $table->integer('memory')->unsigned();
            $table->mediumInteger('memory_overallocate')->unsigned()->nullable();
            $table->integer('disk')->unsigned();
            $table->mediumInteger('disk_overallocate')->unsigned()->nullable();
            $table->char('daemonSecret', 36)->unique();
            $table->smallInteger('daemonListen')->unsigned()->default(8080);
            $table->smallInteger('daemonSFTP')->unsigned()->default(2022);
            $table->string('daemonBase')->default('/home/daemon-files');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
}
