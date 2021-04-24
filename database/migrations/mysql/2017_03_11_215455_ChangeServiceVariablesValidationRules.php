<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeServiceVariablesValidationRules extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_variables', function (Blueprint $table) {
            $table->renameColumn('regex', 'rules');
        });

        DB::transaction(function () {
            foreach (DB::table('service_variables')->get() as $variable) {
                $variable->rules = ($variable->required) ? 'required|regex:' . $variable->rules : 'regex:' . $variable->rules;
                $variable->save();
            }
        });

        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropColumn('required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_variables', function (Blueprint $table) {
            $table->renameColumn('rules', 'regex');
            $table->boolean('required')->default(true)->before('regex');
        });

        DB::transaction(function () {
            foreach (DB::table('service_variables')->get() as $variable) {
                $variable->regex = str_replace(['required|regex:', 'regex:'], '', $variable->regex);
                $variable->save();
            }
        });
    }
}
