<?php

use Pterodactyl\Models\ServiceOption;
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
            // Attempt to fix any startup commands for servers
            // that we possibly can. Also set new containers.
            foreach (ServiceOption::with('servers')->get() as $option) {
                $option->servers->each(function ($s) use ($option) {
                    $prepend = $option->display_executable;
                    $prepend = ($prepend === './ShooterGameServer') ? './ShooterGame/Binaries/Linux/ShooterGameServer' : $prepend;
                    $prepend = ($prepend === 'TerrariaServer.exe') ? 'mono TerrariaServer.exe' : $prepend;

                    $s->startup = $prepend . ' ' . $s->startup;

                    $container = $s->container;
                    if (starts_with($container, 'quay.io/pterodactyl/minecraft')) {
                        $s->container = 'quay.io/pterodactyl/core:java';
                    } elseif (starts_with($container, 'quay.io/pterodactyl/srcds')) {
                        $s->container = 'quay.io/pterodactyl/core:source';
                    } elseif (starts_with($container, 'quay.io/pterodactyl/voice')) {
                        $s->container = 'quay.io/pterodactyl/core:glibc';
                    } elseif (starts_with($container, 'quay.io/pterodactyl/terraria')) {
                        $s->container = 'quay.io/pterodactyl/core:mono'
                    }

                    $s->save();
                });
            }

            Schema::table('services', function (Blueprint $table) {
                $table->renameColumn('file', 'folder');
                $table->dropColumn('executable');
                $table->text('description')->nullable()->change();
                $table->text('startup')->nullable()->change();
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
