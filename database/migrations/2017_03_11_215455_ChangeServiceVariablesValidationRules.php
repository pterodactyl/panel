<?php

use Illuminate\Support\Facades\Schema;
use Pterodactyl\Models\ServiceVariable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeServiceVariablesValidationRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_variables', function (Blueprint $table) {
            $table->renameColumn('regex', 'rules');
        });

        DB::transaction(function () {
            foreach(ServiceVariable::all() as $variable) {
                $variable->rules = ($variable->required) ? 'required|regex:' . $variable->rules : 'regex:' . $variable->regex;
                $variable->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_variables', function (Blueprint $table) {
            $table->renameColumn('rules', 'regex');
        });

        DB::transaction(function () {
            foreach(ServiceVariable::all() as $variable) {
                $variable->regex = str_replace(['required|regex:', 'regex:'], '', $variable->regex);
                $variable->save();
            }
        });
    }
}
