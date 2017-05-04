<?php

use Illuminate\Database\Migrations\Migration;

class UpdateMisnamedBungee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('service_variables')->select('env_variable')->where('env_variable', 'BUNGE_VERSION')->update([
            'env_variable' => 'BUNGEE_VERSION',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
