<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ServicesToNestsConversion extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('services', 'nests');

        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'nest_id');

            $table->foreign('nest_id')->references('id')->on('nests');
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'nest_id');

            $table->foreign('nest_id')->references('id')->on('nests')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('nests', 'services');

        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['nest_id']);
            $table->renameColumn('nest_id', 'service_id');

            $table->foreign('service_id')->references('id')->on('services');
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['nest_id']);
            $table->renameColumn('nest_id', 'service_id');

            $table->foreign('service_id')->references('id')->on('services')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }
}
