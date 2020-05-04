<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAnalyticsToSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('INSERT INTO `settings` (`id`, `key`, `value`) VALUES (\'4\', \'settings::app:analytics\', \'\')');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DELETE FROM `settings` WHERE (`key` = \'settings::app:analytics\')');
    }
}
