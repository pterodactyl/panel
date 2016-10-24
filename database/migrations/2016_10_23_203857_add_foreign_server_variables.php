<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignServerVariables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE server_variables
            MODIFY COLUMN server_id INT(10) UNSIGNED NULL,
            MODIFY COLUMN variable_id INT(10) UNSIGNED NOT NULL
        ');

        Schema::table('server_variables', function (Blueprint $table) {
            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('variable_id')->references('id')->on('service_variables');
        });
    }

      /**
       * Reverse the migrations.
       *
       * @return void
       */
      public function down()
      {
          Schema::table('server_variables', function (Blueprint $table) {
              $table->dropForeign('server_variables_server_id_foreign');
              $table->dropForeign('server_variables_variable_id_foreign');

              $table->dropIndex('server_variables_server_id_foreign');
              $table->dropIndex('server_variables_variable_id_foreign');
          });

          DB::statement('ALTER TABLE allocations
              MODIFY COLUMN server_id MEDIUMINT(8) UNSIGNED NULL,
              MODIFY COLUMN variable_id MEDIUMINT(8) UNSIGNED NOT NULL
          ');
      }

}
