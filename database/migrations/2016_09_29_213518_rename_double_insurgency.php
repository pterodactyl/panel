<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Pterodactyl\Models\ServiceOptions;

class RenameDoubleInsurgency extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = ServiceOptions::where('parent_service', 2)->where('id', 3)->where('name', 'Insurgency')->first();
        if ($model) {
            $model->name = 'Team Fortress 2';
            $model->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
