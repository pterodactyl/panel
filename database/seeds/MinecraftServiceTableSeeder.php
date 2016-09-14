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

class MinecraftServiceTableSeeder extends Seeder
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
            'name' => 'Minecraft',
            'description' => 'Minecraft - the classic game from Mojang. With support for Vanilla MC, Spigot, and many others!',
            'file' => 'minecraft',
            'executable' => 'java',
            'startup' => '-Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}'
        ]);
    }

    private function addCoreOptions()
    {
        $this->option['vanilla'] = Models\ServiceOptions::create([
            'parent_service' => $this->service->id,
            'name' => 'Vanilla Minecraft',
            'description' => 'Minecraft is a game about placing blocks and going on adventures. Explore randomly generated worlds and build amazing things from the simplest of homes to the grandest of castles. Play in Creative Mode with unlimited resources or mine deep in Survival Mode, crafting weapons and armor to fend off dangerous mobs. Do all this alone or with friends.',
            'tag' => 'vanilla',
            'docker_image' => 'quay.io/pterodactyl/minecraft',
            'executable' => null,
            'startup' => null
        ]);
    }

    private function addVariables()
    {
        Models\ServiceVariables::create([
            'option_id' => $this->option['vanilla']->id,
            'name' => 'Server Jar File',
            'description' => 'The name of the server jarfile to run the server with.',
            'env_variable' => 'SERVER_JARFILE',
            'default_value' => 'server.jar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^([\w\d._-]+)(\.jar)$/'
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['vanilla']->id,
            'name' => 'Server Jar File',
            'description' => 'The version of Minecraft Vanilla to install. Use "latest" to install the latest version..',
            'env_variable' => 'VANILLA_VERSION',
            'default_value' => 'latest',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^(latest|[a-zA-Z0-9_\.-]{5,6})$/'
        ]);
    }
}
