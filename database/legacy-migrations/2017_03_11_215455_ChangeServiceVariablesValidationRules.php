<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeServiceVariablesValidationRules extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_variables', function (Blueprint $table): void {
            $table->renameColumn('regex', 'rules');
        });

        DB::transaction(function (): void {
            foreach (DB::table('service_variables')->get() as $variable) {
                $variable->rules = ($variable->required) ? 'required|regex:'.$variable->rules : 'regex:'.$variable->rules;
                $variable->save();
            }
        });

        Schema::table('service_variables', function (Blueprint $table): void {
            $table->dropColumn('required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_variables', function (Blueprint $table): void {
            $table->renameColumn('rules', 'regex');
            $table->boolean('required')->default(true)->before('regex');
        });

        DB::transaction(function (): void {
            foreach (DB::table('service_variables')->get() as $variable) {
                $variable->regex = str_replace(['required|regex:', 'regex:'], '', $variable->regex);
                $variable->save();
            }
        });
    }
}
