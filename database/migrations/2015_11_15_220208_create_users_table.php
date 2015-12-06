<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->char('uuid', 36)->unique();
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->text('password');
            $table->char('language', 2);
            $table->char('session_id', 12);
            $table->string('session_ip');
            $table->tinyInteger('root_admin')->unsigned();
            $table->tinyInteger('use_totp')->unsigned();
            $table->char('totp_secret', 16);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('short')->unique();
            $table->string('long');
        });

        Schema::create('nodes', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->smallInteger('public')->unsigned();
            $table->string('name');
            $table->mediumInteger('location')->unsigned();
            $table->string('fqdn')->unique();
            $table->string('ip');
            $table->integer('memory')->unsigned();
            $table->integer('disk')->unsigned();
            $table->char('daemonSecret', 36)->unique();
            $table->smallInteger('daemonListen')->unsigned()->default(5656);
            $table->smallInteger('daemonSFTP')->unsigned()->default(22);
            $table->string('daemonBase')->default('/home/');
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->char('uuid', 36)->unique();
            $table->mediumInteger('node')->unsigned();
            $table->string('name');
            $table->tinyInteger('active')->unsigned();
            $table->mediumInteger('owner')->unsigned();
            $table->integer('memory')->unsigned();
            $table->integer('disk')->unsigned();
            $table->integer('io')->unsigned();
            $table->integer('cpu')->unsigned();
            $table->string('ip');
            $table->integer('port')->unsigned();
            $table->text('daemonStartup')->nullable();
            $table->text('daemonSecret');
            $table->string('username');
            $table->integer('installed')->unsigned()->default(0);
            $table->timestamps();
        });

        Schema::create('daemon', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->mediumInteger('server')->unsigned();
            $table->string('parameter');
            $table->text('value');
            $table->tinyInteger('editable')->unsigned()->default(0);
            $table->tinyInteger('visible')->unsigned()->default(0);
            $table->text('regex')->nullable();
            $table->timestamps();
        });

        Schema::create('allocations', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->mediumInteger('node')->unsigned();
            $table->string('ip');
            $table->mediumInteger('port');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('allocations');
        Schema::dropIfExists('daemon');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('nodes');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');
    }
}
