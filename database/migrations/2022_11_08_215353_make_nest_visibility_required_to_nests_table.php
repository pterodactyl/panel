<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Models\Nest;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nests', function (Blueprint $table) {
            $table->boolean('private')->default(false)->change();

            $nests = Nest::where('private', null)->get();
            foreach ($nests as $nest) {
                $nest->update(['private', false]);
            };
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nests', function (Blueprint $table) {
            $table->boolean('private')->nullable()->default(false)->change();
        });
    }
};
