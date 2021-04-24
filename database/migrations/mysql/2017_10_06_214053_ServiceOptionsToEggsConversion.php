<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ServiceOptionsToEggsConversion extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['config_from']);
            $table->dropForeign(['copy_script_from']);
        });

        Schema::rename('service_options', 'eggs');

        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->renameColumn('option_id', 'egg_id');

            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('CASCADE');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->renameColumn('option_id', 'egg_id');

            $table->foreign('egg_id')->references('id')->on('eggs');
        });

        Schema::table('eggs', function (Blueprint $table) {
            $table->foreign('config_from')->references('id')->on('eggs')->onDelete('SET NULL');
            $table->foreign('copy_script_from')->references('id')->on('eggs')->onDelete('SET NULL');
        });

        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->renameColumn('option_id', 'egg_id');

            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('eggs', function (Blueprint $table) {
            $table->dropForeign(['config_from']);
            $table->dropForeign(['copy_script_from']);
        });

        Schema::rename('eggs', 'service_options');

        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['egg_id']);
            $table->renameColumn('egg_id', 'option_id');

            $table->foreign('option_id')->references('id')->on('service_options')->onDelete('CASCADE');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['egg_id']);
            $table->renameColumn('egg_id', 'option_id');

            $table->foreign('option_id')->references('id')->on('service_options');
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->foreign('config_from')->references('id')->on('service_options')->onDelete('SET NULL');
            $table->foreign('copy_script_from')->references('id')->on('service_options')->onDelete('SET NULL');
        });

        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign(['egg_id']);
            $table->renameColumn('egg_id', 'option_id');

            $table->foreign('option_id')->references('id')->on('options')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }
}
