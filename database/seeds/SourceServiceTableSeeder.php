<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
use Pterodactyl\Models;
use Illuminate\Database\Seeder;

class SourceServiceTableSeeder extends Seeder
{
    /**
     * The core service ID.
     *
     * @var Models\Service
     */
    protected $service;

    /**
     * Stores all of the option objects.
     *
     * @var array
     */
    protected $option = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->addCoreService();
        $this->addCoreOptions();
        $this->addVariables();
    }

    private function addCoreService()
    {
        $this->service = Models\Service::create([
            'author' => 'ptrdctyl-v040-11e6-8b77-86f30ca893d3',
            'name' => 'Source Engine',
            'description' => 'Includes support for most Source Dedicated Server games.',
            'file' => 'srcds',
            'executable' => './srcds_run',
            'startup' => '-game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} -strictportbind -norestart',
        ]);
    }

    private function addCoreOptions()
    {
        $this->option['insurgency'] = Models\ServiceOption::create([
            'service_id' => $this->service->id,
            'name' => 'Insurgency',
            'description' => 'Take to the streets for intense close quarters combat, where a team\'s survival depends upon securing crucial strongholds and destroying enemy supply in this multiplayer and cooperative Source Engine based experience.',
            'tag' => 'srcds',
            'docker_image' => 'quay.io/pterodactyl/srcds',
            'executable' => null,
            'startup' => '-game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +map {{SRCDS_MAP}} -strictportbind -norestart',
        ]);

        $this->option['tf2'] = Models\ServiceOption::create([
            'service_id' => $this->service->id,
            'name' => 'Team Fortress 2',
            'description' => 'Team Fortress 2 is a team-based first-person shooter multiplayer video game developed and published by Valve Corporation. It is the sequel to the 1996 mod Team Fortress for Quake and its 1999 remake.',
            'tag' => 'srcds',
            'docker_image' => 'quay.io/pterodactyl/srcds',
            'executable' => null,
            'startup' => '-game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +map {{SRCDS_MAP}} -strictportbind -norestart',
        ]);

        $this->option['ark'] = Models\ServiceOption::create([
            'service_id' => $this->service->id,
            'name' => 'Ark: Survival Evolved',
            'description' => 'As a man or woman stranded, naked, freezing, and starving on the unforgiving shores of a mysterious island called ARK, use your skill and cunning to kill or tame and ride the plethora of leviathan dinosaurs and other primeval creatures roaming the land. Hunt, harvest resources, craft items, grow crops, research technologies, and build shelters to withstand the elements and store valuables, all while teaming up with (or preying upon) hundreds of other players to survive, dominate... and escape! — Gamepedia: ARK',
            'tag' => 'ark',
            'docker_image' => 'quay.io/pterodactyl/srcds:ark',
            'executable' => './ShooterGameServer',
            'startup' => 'TheIsland?listen?ServerPassword={{ARK_PASSWORD}}?ServerAdminPassword={{ARK_ADMIN_PASSWORD}}?Port={{SERVER_PORT}}?MaxPlayers={{SERVER_MAX_PLAYERS}}',
        ]);

        $this->option['custom'] = Models\ServiceOption::create([
            'service_id' => $this->service->id,
            'name' => 'Custom Source Engine Game',
            'description' => 'This option allows modifying the startup arguments and other details to run a custo SRCDS based game on the panel.',
            'tag' => 'srcds',
            'docker_image' => 'quay.io/pterodactyl/srcds',
            'executable' => null,
            'startup' => null,
        ]);
    }

    private function addVariables()
    {
        $this->addInsurgencyVariables();
        $this->addTF2Variables();
        $this->addArkVariables();
        $this->addCustomVariables();
    }

    private function addInsurgencyVariables()
    {
        Models\ServiceVariable::create([
            'option_id' => $this->option['insurgency']->id,
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_APPID',
            'default_value' => '17705',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(17705)$/',
        ]);

        Models\ServiceVariable::create([
            'option_id' => $this->option['insurgency']->id,
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_GAME',
            'default_value' => 'insurgency',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(insurgency)$/',
        ]);

        Models\ServiceVariable::create([
            'option_id' => $this->option['insurgency']->id,
            'name' => 'Default Map',
            'description' => 'The default map to use when starting the server.',
            'env_variable' => 'SRCDS_MAP',
            'default_value' => 'sinjar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^(\w{1,20})$/',
        ]);
    }

    private function addTF2Variables()
    {
        Models\ServiceVariable::create([
            'option_id' => $this->option['tf2']->id,
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_APPID',
            'default_value' => '232250',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(232250)$/',
        ]);

        Models\ServiceVariable::create([
            'option_id' => $this->option['tf2']->id,
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_GAME',
            'default_value' => 'tf',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(tf)$/',
        ]);

        Models\ServiceVariable::create([
            'option_id' => $this->option['tf2']->id,
            'name' => 'Default Map',
            'description' => 'The default map to use when starting the server.',
            'env_variable' => 'SRCDS_MAP',
            'default_value' => 'cp_dustbowl',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^(\w{1,20})$/',
        ]);
    }

    private function addArkVariables()
    {
        DB::table('service_variables')->insert([
            'option_id' => $this->option['ark']->id,
            'name' => 'Server Password',
            'description' => 'If specified, players must provide this password to join the server.',
            'env_variable' => 'ARK_PASSWORD',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 0,
            'regex' => '/^(\w\.*)$/',
        ]);

        DB::table('service_variables')->insert([
            'option_id' => $this->option['ark']->id,
            'name' => 'Admin Password',
            'description' => 'If specified, players must provide this password (via the in-game console) to gain access to administrator commands on the server.',
            'env_variable' => 'ARK_ADMIN_PASSWORD',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 0,
            'regex' => '/^(\w\.*)$/',
        ]);

        DB::table('service_variables')->insert([
            'option_id' => $this->option['ark']->id,
            'name' => 'Maximum Players',
            'description' => 'Specifies the maximum number of players that can play on the server simultaneously.',
            'env_variable' => 'SERVER_MAX_PLAYERS',
            'default_value' => 20,
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^(\d{1,4})$/',
        ]);
    }

    private function addCustomVariables()
    {
        Models\ServiceVariable::create([
            'option_id' => $this->option['custom']->id,
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_APPID',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(\d){1,6}$/',
        ]);

        Models\ServiceVariable::create([
            'option_id' => $this->option['custom']->id,
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_GAME',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(.*)$/',
        ]);
    }
}
