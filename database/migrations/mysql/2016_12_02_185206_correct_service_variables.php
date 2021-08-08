<?php

use Illuminate\Database\Migrations\Migration;

class CorrectServiceVariables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::transaction(function () {
            // Modify Default Spigot Startup Line
            DB::table('service_options')->where([
                ['name', 'Spigot'],
                ['tag', 'spigot'],
                ['startup', '-Xms128M -Xmx{{SERVER_MEMORY}}M -Djline.terminal=jline.UnsupportedTerminal -jar {{SERVER_JARFILE}}'],
            ])->update([
                'startup' => null,
            ]);

            // Correct Spigot Version Checking
            DB::table('service_variables')->where([
                ['name', 'Spigot Version'],
                ['env_variable', 'DL_VERSION'],
                ['default_value', 'latest'],
                ['regex', '/^(latest|[a-zA-Z0-9_\.-]{5,6})$/'],
            ])->update([
                'regex' => '/^(latest|[a-zA-Z0-9_\.-]{3,7})$/',
            ]);

            // Correct Vanilla Version Checking (as well as naming)
            DB::table('service_variables')->where([
                ['name', 'Server Jar File'],
                ['env_variable', 'VANILLA_VERSION'],
                ['default_value', 'latest'],
                ['regex', '/^(latest|[a-zA-Z0-9_\.-]{5,6})$/'],
            ])->update([
                'name' => 'Server Version',
                'regex' => '/^(latest|[a-zA-Z0-9_\.-]{3,7})$/',
            ]);

            // Update Sponge Version Checking and Update Default Version
            DB::table('service_variables')->where([
                ['name', 'Sponge Version'],
                ['env_variable', 'SPONGE_VERSION'],
                ['default_value', '1.8.9-4.2.0-BETA-351'],
                ['regex', '/^(.*)$/'],
            ])->update([
                'default_value' => '1.10.2-5.1.0-BETA-359',
                'regex' => '/^([a-zA-Z0-9.\-_]+)$/',
            ]);

            // Update Bungeecord Version Checking
            DB::table('service_variables')->where([
                ['name', 'Bungeecord Version'],
                ['env_variable', 'BUNGEE_VERSION'],
                ['default_value', 'latest'],
                ['regex', '/^(latest|[\d]{3,5})$/'],
            ])->update([
                'regex' => '/^(latest|[\d]{1,6})$/',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // do nothing
    }
}
