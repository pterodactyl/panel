<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteServiceExecutableOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::table('services', function (Blueprint $table) {
                $table->renameColumn('file', 'folder');
                $table->text('description')->nullable()->change();
                $table->text('startup')->nullable()->change();
            });

            // Attempt to fix any startup commands for servers
            // that we possibly can.
            foreach (ServiceOption::with('servers')->get() as $option) {
                $option->servers->each(function ($s) use ($option) {
                    $prepend = $option->display_executable;
                    $prepend = ($prepend === './ShooterGameServer') ? './ShooterGame/Binaries/Linux/ShooterGameServer' : $prepend;
                    $prepend = ($prepend === 'TerrariaServer.exe') ? 'mono TerrariaServer.exe' : $prepend;

                    $s->startup = $prepend . ' ' . $s->startup;
                    $s->save();
                });
            }

            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('executable');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('executable')->after('folder');
            $table->renameColumn('folder', 'file');
            $table->text('description')->nullable(false)->change();
            $table->text('startup')->nullable(false)->change();
        });
    }
}
