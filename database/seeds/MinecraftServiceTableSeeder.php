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
use Illuminate\Database\Seeder;
use Pterodactyl\Models\Service;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Models\ServiceVariable;

class MinecraftServiceTableSeeder extends Seeder
{
    /**
     * The core service ID.
     *
     * @var \Pterodactyl\Models\Service
     */
    protected $service;

    /**
     * Stores all of the option objects.
     *
     * @var array
     */
    protected $option = [];

    private $default_mc = <<<'EOF'
'use strict';

/**
 * Pterodactyl - Daemon
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
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
const rfr = require('rfr');
const _ = require('lodash');

const Core = rfr('src/services/index.js');

class Service extends Core {
    onConsole(data) {
        // Hide the output spam from Bungeecord getting pinged.
        if (_.endsWith(data, '<-> InitialHandler has connected')) return;
        return super.onConsole(data);
    }
}

module.exports = Service;
EOF;

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
        $this->service = Service::updateOrCreate([
            'author' => config('pterodactyl.service.core'),
            'folder' => 'minecraft',
        ], [
            'name' => 'Minecraft',
            'description' => 'Minecraft - the classic game from Mojang. With support for Vanilla MC, Spigot, and many others!',
            'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}',
            'index_file' => $this->default_mc,
        ]);
    }

    private function addCoreOptions()
    {
        $this->option['vanilla'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'vanilla',
        ], [
            'name' => 'Vanilla Minecraft',
            'description' => 'Minecraft is a game about placing blocks and going on adventures. Explore randomly generated worlds and build amazing things from the simplest of homes to the grandest of castles. Play in Creative Mode with unlimited resources or mine deep in Survival Mode, crafting weapons and armor to fend off dangerous mobs. Do all this alone or with friends.',
            'docker_image' => 'quay.io/pterodactyl/minecraft',
            'config_startup' => '{"done": ")! For help, type ", "userInteraction": [ "Go to eula.txt for more info."]}',
            'config_logs' => '{"custom": false, "location": "logs/latest.log"}',
            'config_files' => '{"server.properties":{"parser": "properties", "find":{"server-ip": "0.0.0.0", "enable-query": "true", "server-port": "{{server.build.default.port}}", "query.port": "{{server.build.default.port}}"}}}',
            'config_stop' => 'stop',
            'config_from' => null,
            'startup' => null,
        ]);

        $this->option['spigot'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'spigot',
        ], [
            'name' => 'Spigot',
            'description' => 'Spigot is the most widely-used modded Minecraft server software in the world. It powers many of the top Minecraft server networks around to ensure they can cope with their huge player base and ensure the satisfaction of their players. Spigot works by reducing and eliminating many causes of lag, as well as adding in handy features and settings that help make your job of server administration easier.',
            'docker_image' => 'quay.io/pterodactyl/minecraft:spigot',
            'config_startup' => null,
            'config_files' => '{"spigot.yml":{"parser": "yaml", "find":{"settings.restart-on-crash": "false"}}}',
            'config_logs' => null,
            'config_stop' => null,
            'config_from' => $this->option['vanilla']->id,
            'startup' => null,
        ]);

        $this->option['sponge'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'sponge',
        ], [
            'name' => 'Sponge (SpongeVanilla)',
            'description' => 'SpongeVanilla is the SpongeAPI implementation for Vanilla Minecraft.',
            'docker_image' => 'quay.io/pterodactyl/minecraft:sponge',
            'config_startup' => '{"userInteraction": [ "You need to agree to the EULA"]}',
            'config_files' => null,
            'config_logs' => null,
            'config_stop' => null,
            'config_from' => $this->option['vanilla']->id,
            'startup' => null,
        ]);

        $this->option['bungeecord'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'bungeecord',
        ], [
            'name' => 'Bungeecord',
            'description' => 'For a long time, Minecraft server owners have had a dream that encompasses a free, easy, and reliable way to connect multiple Minecraft servers together. BungeeCord is the answer to said dream. Whether you are a small server wishing to string multiple game-modes together, or the owner of the ShotBow Network, BungeeCord is the ideal solution for you. With the help of BungeeCord, you will be able to unlock your community\'s full potential.',
            'docker_image' => 'quay.io/pterodactyl/minecraft:bungeecord',
            'config_startup' => '{"done": "Listening on ", "userInteraction": [ "Listening on /0.0.0.0:25577"]}',
            'config_files' => '{"config.yml":{"parser": "yaml", "find":{"listeners[0].query_enabled": true, "listeners[0].query_port": "{{server.build.default.port}}", "listeners[0].host": "0.0.0.0:{{server.build.default.port}}", "servers.*.address":{"127.0.0.1": "{{config.docker.interface}}", "localhost": "{{config.docker.interface}}"}}}}',
            'config_logs' => '{"custom": false, "location": "proxy.log.0"}',
            'config_stop' => 'end',
            'config_from' => null,
            'startup' => null,
        ]);
    }

    private function addVariables()
    {
        $this->addVanillaVariables();
        $this->addSpigotVariables();
        $this->addSpongeVariables();
        $this->addBungeecordVariables();
    }

    private function addVanillaVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['vanilla']->id,
            'env_variable' => 'SERVER_JARFILE',
        ], [
            'name' => 'Server Jar File',
            'description' => 'The name of the server jarfile to run the server with.',
            'default_value' => 'server.jar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([\w\d._-]+)(\.jar)$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['vanilla']->id,
            'env_variable' => 'VANILLA_VERSION',
        ], [
            'name' => 'Server Version',
            'description' => 'The version of Minecraft Vanilla to install. Use "latest" to install the latest version.',
            'default_value' => 'latest',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string|between:3,7',
        ]);
    }

    private function addSpigotVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['spigot']->id,
            'env_variable' => 'SERVER_JARFILE',
        ], [
            'name' => 'Server Jar File',
            'description' => 'The name of the server jarfile to run the server with.',
            'default_value' => 'server.jar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([\w\d._-]+)(\.jar)$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['spigot']->id,
            'env_variable' => 'DL_VERSION',
        ], [
            'name' => 'Spigot Version',
            'description' => 'The version of Spigot to download (using the --rev tag). Use "latest" for latest.',
            'default_value' => 'latest',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string|between:3,7',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['spigot']->id,
            'env_variable' => 'DL_PATH',
        ], [
            'name' => 'Download Path',
            'description' => 'A URL to use to download Spigot rather than building it on the server. This is not user viewable. Use <code>{{DL_VERSION}}</code> in the URL to automatically insert the assigned version into the URL. If you do not enter a URL Spigot will build directly in the container (this will fail on low memory containers).',
            'default_value' => '',
            'user_viewable' => 0,
            'user_editable' => 0,
            'rules' => 'string',
        ]);
    }

    private function addSpongeVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['sponge']->id,
            'env_variable' => 'SPONGE_VERSION',
        ], [
            'name' => 'Sponge Version',
            'description' => 'The version of SpongeVanilla to download and use.',
            'default_value' => '1.10.2-5.2.0-BETA-381',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|regex:/^([a-zA-Z0-9.\-_]+)$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['sponge']->id,
            'env_variable' => 'SERVER_JARFILE',
        ], [
            'name' => 'Server Jar File',
            'description' => 'The name of the Jarfile to use when running SpongeVanilla.',
            'default_value' => 'server.jar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([\w\d._-]+)(\.jar)$/',
        ]);
    }

    private function addBungeecordVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['bungeecord']->id,
            'env_variable' => 'BUNGEE_VERSION',
        ], [
            'name' => 'Bungeecord Version',
            'description' => 'The version of Bungeecord to download and use.',
            'default_value' => 'latest',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|alpha_num|between:1,6',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['bungeecord']->id,
            'env_variable' => 'SERVER_JARFILE',
        ], [
            'name' => 'Bungeecord Jar File',
            'description' => 'The name of the Jarfile to use when running Bungeecord.',
            'default_value' => 'bungeecord.jar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([\w\d._-]+)(\.jar)$/',
        ]);
    }
}
