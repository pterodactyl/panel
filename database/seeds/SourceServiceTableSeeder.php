<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
use Illuminate\Database\Seeder;

use Pterodactyl\Models;

class SourceServiceTableSeeder extends Seeder
{
    /**
     * The core service ID.
     *
     * @var Models\Service
     */
    protected $service;

    /**
     * Stores all of the option objects
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
            'file' => 'srcds',
            'executable' => './srcds_run',
            'startup' => '-game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} -strictportbind -norestart'
        ]);
    }

    private function addCoreOptions()
    {
        $this->option['insurgency'] = Models\ServiceOptions::create([
            'parent_service' => $this->service->id,
            'name' => 'Insurgency',
            'description' => 'Take to the streets for intense close quarters combat, where a team\'s survival depends upon securing crucial strongholds and destroying enemy supply in this multiplayer and cooperative Source Engine based experience.',
            'tag' => 'srcds',
            'docker_image' => 'quay.io/pterodactyl/srcds',
            'executable' => null,
            'startup' => '-game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +map {{SRCDS_MAP}} -strictportbind -norestart'
        ]);

        $this->option['tf2'] = Models\ServiceOptions::create([
            'parent_service' => $this->service->id,
            'name' => 'Insurgency',
            'description' => 'Team Fortress 2 is a team-based first-person shooter multiplayer video game developed and published by Valve Corporation. It is the sequel to the 1996 mod Team Fortress for Quake and its 1999 remake.',
            'tag' => 'srcds',
            'docker_image' => 'quay.io/pterodactyl/srcds',
            'executable' => null,
            'startup' => '-game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +map {{SRCDS_MAP}} -strictportbind -norestart'
        ]);

        $this->option['custom'] = Models\ServiceOptions::create([
            'parent_service' => $this->service->id,
            'name' => 'Custom Source Engine Game',
            'description' => 'This option allows modifying the startup arguments and other details to run a custo SRCDS based game on the panel.',
            'tag' => 'srcds',
            'docker_image' => 'quay.io/pterodactyl/srcds',
            'executable' => null,
            'startup' => null
        ]);
    }

    private function addVariables()
    {
        $this->addInsurgencyVariables();
        $this->addTF2Variables();
        $this->addCustomVariables();
    }

    private function addInsurgencyVariables()
    {
        Models\ServiceVariables::create([
            'option_id' => $this->option['insurgency']->id,
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_APPID',
            'default_value' => '17705',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(17705)$/'
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['insurgency']->id,
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_GAME',
            'default_value' => 'insurgency',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(insurgency)$/'
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['insurgency']->id,
            'name' => 'Default Map',
            'description' => 'The default map to use when starting the server.',
            'env_variable' => 'SRCDS_MAP',
            'default_value' => 'sinjar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^(\w{1,20})$/'
        ]);
    }

    private function addTF2Variables()
    {
        Models\ServiceVariables::create([
            'option_id' => $this->option['tf2']->id,
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_APPID',
            'default_value' => '232250',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(232250)$/'
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['tf2']->id,
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_GAME',
            'default_value' => 'tf',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(tf)$/'
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['tf2']->id,
            'name' => 'Default Map',
            'description' => 'The default map to use when starting the server.',
            'env_variable' => 'SRCDS_MAP',
            'default_value' => 'cp_dustbowl',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^(\w{1,20})$/'
        ]);
    }

    private function addCustomVariables()
    {
        Models\ServiceVariables::create([
            'option_id' => $this->option['custom']->id,
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_APPID',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(\d){1,6}$/'
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['custom']->id,
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'env_variable' => 'SRCDS_GAME',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(.*)$/'
        ]);
    }
}
