<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Pterodactyl\Models\Service;
use Pterodactyl\Models\ServiceOptions;
use Pterodactyl\Models\ServiceVariables;

class AddArkServiceOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $service = Service::select('id')->where('author', 'ptrdctyl-v040-11e6-8b77-86f30ca893d3')->where('name', 'Source Engine')->first();
            if (!$service) {
                exit('No service could be found.');
            }

            $option = ServiceOptions::create([
                'parent_service' => $service->id,
                'name' => 'Ark: Survival Evolved',
                'description' => 'As a man or woman stranded, naked, freezing, and starving on the unforgiving shores of a mysterious island called ARK, use your skill and cunning to kill or tame and ride the plethora of leviathan dinosaurs and other primeval creatures roaming the land. Hunt, harvest resources, craft items, grow crops, research technologies, and build shelters to withstand the elements and store valuables, all while teaming up with (or preying upon) hundreds of other players to survive, dominate... and escape! — Gamepedia: ARK',
                'tag' => 'ark',
                'docker_image' => 'quay.io/pterodactyl/srcds:ark',
                'executable' => './ShooterGameServer',
                'startup' => 'TheIsland?listen?ServerPassword={{ARK_PASSWORD}}?ServerAdminPassword={{ARK_ADMIN_PASSWORD}}?Port={{SERVER_PORT}}?MaxPlayers={{SERVER_MAX_PLAYERS}}'
            ]);

            ServiceVariables::create([
                'option_id' => $option->id,
                'name' => 'Server Password',
                'description' => 'If specified, players must provide this password to join the server.',
                'env_variable' => 'ARK_PASSWORD',
                'default_value' => '',
                'user_viewable' => 1,
                'user_editable' => 1,
                'required' => 0,
                'regex' => '/^(\w\.*)$/'
            ]);

            ServiceVariables::create([
                'option_id' => $option->id,
                'name' => 'Admin Password',
                'description' => 'If specified, players must provide this password (via the in-game console) to gain access to administrator commands on the server.',
                'env_variable' => 'ARK_ADMIN_PASSWORD',
                'default_value' => '',
                'user_viewable' => 1,
                'user_editable' => 1,
                'required' => 0,
                'regex' => '/^(\w\.*)$/'
            ]);

            ServiceVariables::create([
                'option_id' => $option->id,
                'name' => 'Maximum Players',
                'description' => 'Specifies the maximum number of players that can play on the server simultaneously.',
                'env_variable' => 'SERVER_MAX_PLAYERS',
                'default_value' => 20,
                'user_viewable' => 1,
                'user_editable' => 1,
                'required' => 1,
                'regex' => '/^(\d{1,4})$/'
            ]);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            $service = Service::select('id')->where('author', 'ptrdctyl-v040-11e6-8b77-86f30ca893d3')->where('name', 'Source Engine')->first();
            $option = ServiceOptions::where('parent_service', $service->id)->where('tag', 'ark')->delete();
            $variables = ServiceVariables::where('option_id', $option->id)->delete();
        });
    }
}
