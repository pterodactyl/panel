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
use Pterodactyl\Traits\Services\CreatesServiceIndex;

class SourceServiceTableSeeder extends Seeder
{
    use CreatesServiceIndex;

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
        $this->service = Service::updateOrCreate([
            'author' => config('pterodactyl.service.core'),
            'folder' => 'srcds',
        ], [
            'name' => 'Source Engine',
            'description' => 'Includes support for most Source Dedicated Server games.',
            'startup' => './srcds_run -game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +ip 0.0.0.0 -strictportbind -norestart',
            'index_file' => $this->getIndexScript(),
        ]);
    }

    private function addCoreOptions()
    {
        $script = <<<'EOF'
#!/bin/bash
# SRCDS Base Installation Script
#
# Server Files: /mnt/server
apt -y update
apt -y --no-install-recommends install curl lib32gcc1 ca-certificates

cd /tmp
curl -sSL -o steamcmd.tar.gz http://media.steampowered.com/installer/steamcmd_linux.tar.gz

mkdir -p /mnt/server/steamcmd
tar -xzvf steamcmd.tar.gz -C /mnt/server/steamcmd
cd /mnt/server/steamcmd

# SteamCMD fails otherwise for some reason, even running as root.
# This is changed at the end of the install process anyways.
chown -R root:root /mnt

export HOME=/mnt/server
./steamcmd.sh +login anonymous +force_install_dir /mnt/server +app_update ${SRCDS_APPID} +quit

mkdir -p /mnt/server/.steam/sdk32
cp -v linux32/steamclient.so ../.steam/sdk32/steamclient.so
EOF;

        $this->option['source'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'source',
        ], [
            'name' => 'Custom Source Engine Game',
            'description' => 'This option allows modifying the startup arguments and other details to run a custo SRCDS based game on the panel.',
            'docker_image' => 'quay.io/pterodactyl/core:source',
            'config_startup' => '{"done": "gameserver Steam ID", "userInteraction": []}',
            'config_files' => '{}',
            'config_logs' => '{"custom": true, "location": "logs/latest.log"}',
            'config_stop' => 'quit',
            'config_from' => null,
            'startup' => null,
            'script_install' => $script,
            'script_entry' => 'bash',
            'script_container' => 'ubuntu:16.04',
        ]);

        $this->option['insurgency'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'insurgency',
        ], [
            'name' => 'Insurgency',
            'description' => 'Take to the streets for intense close quarters combat, where a team\'s survival depends upon securing crucial strongholds and destroying enemy supply in this multiplayer and cooperative Source Engine based experience.',
            'docker_image' => 'quay.io/pterodactyl/core:source',
            'config_startup' => null,
            'config_files' => null,
            'config_logs' => null,
            'config_stop' => null,
            'config_from' => $this->option['source']->id,
            'startup' => './srcds_run -game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +map {{SRCDS_MAP}} +ip 0.0.0.0 -strictportbind -norestart',
            'copy_script_from' => $this->option['source']->id,
        ]);

        $this->option['tf2'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'tf2',
        ], [
            'name' => 'Team Fortress 2',
            'description' => 'Team Fortress 2 is a team-based first-person shooter multiplayer video game developed and published by Valve Corporation. It is the sequel to the 1996 mod Team Fortress for Quake and its 1999 remake.',
            'docker_image' => 'quay.io/pterodactyl/core:source',
            'config_startup' => null,
            'config_files' => null,
            'config_logs' => null,
            'config_stop' => null,
            'config_from' => $this->option['source']->id,
            'startup' => './srcds_run -game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +map {{SRCDS_MAP}} +ip 0.0.0.0 -strictportbind -norestart',
            'copy_script_from' => $this->option['source']->id,
        ]);

        $script = <<<'EOF'
#!/bin/bash
# ARK: Installation Script
#
# Server Files: /mnt/server
apt -y update
apt -y --no-install-recommends install curl lib32gcc1 ca-certificates

cd /tmp
curl -sSL -o steamcmd.tar.gz http://media.steampowered.com/installer/steamcmd_linux.tar.gz

mkdir -p /mnt/server/steamcmd
mkdir -p /mnt/server/Engine/Binaries/ThirdParty/SteamCMD/Linux

tar -xzvf steamcmd.tar.gz -C /mnt/server/steamcmd
tar -xzvf steamcmd.tar.gz -C /mnt/server/Engine/Binaries/ThirdParty/SteamCMD/Linux

cd /mnt/server/steamcmd

# SteamCMD fails otherwise for some reason, even running as root.
# This is changed at the end of the install process anyways.
chown -R root:root /mnt

export HOME=/mnt/server
./steamcmd.sh +login anonymous +force_install_dir /mnt/server +app_update 376030 +quit

mkdir -p /mnt/server/.steam/sdk32
cp -v linux32/steamclient.so ../.steam/sdk32/steamclient.so
EOF;

        $this->option['ark'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'ark',
        ], [
            'name' => 'Ark: Survival Evolved',
            'description' => 'As a man or woman stranded, naked, freezing, and starving on the unforgiving shores of a mysterious island called ARK, use your skill and cunning to kill or tame and ride the plethora of leviathan dinosaurs and other primeval creatures roaming the land. Hunt, harvest resources, craft items, grow crops, research technologies, and build shelters to withstand the elements and store valuables, all while teaming up with (or preying upon) hundreds of other players to survive, dominate... and escape! — Gamepedia: ARK',
            'docker_image' => 'quay.io/pterodactyl/core:source',
            'config_startup' => '{"done": "Setting breakpad minidump AppID"}',
            'config_files' => null,
            'config_logs' => null,
            'config_stop' => '^C',
            'config_from' => $this->option['source']->id,
            'startup' => './ShooterGame/Binaries/Linux/ShooterGameServer TheIsland?listen?ServerPassword={{ARK_PASSWORD}}?ServerAdminPassword={{ARK_ADMIN_PASSWORD}}?Port={{SERVER_PORT}}?MaxPlayers={{SERVER_MAX_PLAYERS}}',
            'script_install' => $script,
            'script_entry' => 'bash',
            'script_container' => 'ubuntu:16.04',
        ]);

        $script = <<<'EOF'
#!/bin/bash
# CSGO Installation Script
#
# Server Files: /mnt/server
apt -y update
apt -y --no-install-recommends install curl lib32gcc1 ca-certificates

cd /tmp
curl -sSL -o steamcmd.tar.gz http://media.steampowered.com/installer/steamcmd_linux.tar.gz

mkdir -p /mnt/server/steamcmd
tar -xzvf steamcmd.tar.gz -C /mnt/server/steamcmd
cd /mnt/server/steamcmd

# SteamCMD fails otherwise for some reason, even running as root.
# This is changed at the end of the install process anyways.
chown -R root:root /mnt

export HOME=/mnt/server
./steamcmd.sh +login anonymous +force_install_dir /mnt/server +app_update 740 +quit

mkdir -p /mnt/server/.steam/sdk32
cp -v linux32/steamclient.so ../.steam/sdk32/steamclient.so
EOF;

        $this->option['csgo'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'csgo',
        ], [
            'name' => 'Counter-Strike: Global Offensive',
            'description' => 'Counter-Strike: Global Offensive is a multiplayer first-person shooter video game developed by Hidden Path Entertainment and Valve Corporation.',
            'docker_image' => 'quay.io/pterodactyl/core:source',
            'config_startup' => '{"done": "VAC secure mode is activated.", "userInteraction": []}',
            'config_files' => null,
            'config_logs' => '{"custom": true, "location": "logs/latest.log"}',
            'config_stop' => 'quit',
            'config_from' => $this->option['source']->id,
            'startup' => './srcds_run -game csgo -console -port {{SERVER_PORT}} +ip 0.0.0.0 +map {{SRCDS_MAP}} -strictportbind -norestart +sv_setsteamaccount {{STEAM_ACC}}',
            'script_install' => $script,
            'script_entry' => 'bash',
            'script_container' => 'ubuntu:16.04',
        ]);

        $script = <<<'EOF'
#!/bin/bash
# Garry's Mod Installation Script
#
# Server Files: /mnt/server
apt -y update
apt -y --no-install-recommends install curl lib32gcc1 ca-certificates

cd /tmp
curl -sSL -o steamcmd.tar.gz http://media.steampowered.com/installer/steamcmd_linux.tar.gz

mkdir -p /mnt/server/steamcmd
tar -xzvf steamcmd.tar.gz -C /mnt/server/steamcmd
cd /mnt/server/steamcmd

# SteamCMD fails otherwise for some reason, even running as root.
# This is changed at the end of the install process anyways.
chown -R root:root /mnt

export HOME=/mnt/server
./steamcmd.sh +login anonymous +force_install_dir /mnt/server +app_update 4020 +quit

mkdir -p /mnt/server/.steam/sdk32
cp -v linux32/steamclient.so ../.steam/sdk32/steamclient.so
EOF;

        $this->option['gmod'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'gmod',
        ], [
            'name' => 'Garrys Mod',
            'description' => 'Garrys Mod, is a sandbox physics game created by Garry Newman, and developed by his company, Facepunch Studios.',
            'docker_image' => 'quay.io/pterodactyl/core:source',
            'config_startup' => '{"done": "VAC secure mode is activated.", "userInteraction": []}',
            'config_files' => null,
            'config_logs' => '{"custom": true, "location": "logs/latest.log"}',
            'config_stop' => 'quit',
            'config_from' => $this->option['source']->id,
            'startup' => './srcds_run -game garrysmod -console -port {{SERVER_PORT}} +ip 0.0.0.0 +map {{SRCDS_MAP}} -strictportbind -norestart +sv_setsteamaccount {{STEAM_ACC}}',
            'script_install' => $script,
            'script_entry' => 'bash',
            'script_container' => 'ubuntu:16.04',
        ]);
    }

    private function addVariables()
    {
        $this->addInsurgencyVariables();
        $this->addTF2Variables();
        $this->addArkVariables();
        $this->addCSGOVariables();
        $this->addGMODVariables();
        $this->addCustomVariables();
    }

    private function addInsurgencyVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['insurgency']->id,
            'env_variable' => 'SRCDS_APPID',
        ], [
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'default_value' => '17705',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|regex:/^(17705)$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['insurgency']->id,
            'env_variable' => 'SRCDS_GAME',
        ], [
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'default_value' => 'insurgency',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|regex:/^(insurgency)$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['insurgency']->id,
            'env_variable' => 'SRCDS_MAP',
        ], [
            'name' => 'Default Map',
            'description' => 'The default map to use when starting the server.',
            'default_value' => 'sinjar',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^(\w{1,20})$/',
        ]);
    }

    private function addTF2Variables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['tf2']->id,
            'env_variable' => 'SRCDS_APPID',
        ], [
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'default_value' => '232250',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|regex:/^(232250)$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['tf2']->id,
            'env_variable' => 'SRCDS_GAME',
        ], [
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'default_value' => 'tf',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|regex:/^(tf)$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['tf2']->id,
            'env_variable' => 'SRCDS_MAP',
        ], [
            'name' => 'Default Map',
            'description' => 'The default map to use when starting the server.',
            'default_value' => 'cp_dustbowl',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^(\w{1,20})$/',
        ]);
    }

    private function addArkVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['ark']->id,
            'env_variable' => 'ARK_PASSWORD',
        ], [
            'name' => 'Server Password',
            'description' => 'If specified, players must provide this password to join the server.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'alpha_dash|between:1,100',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['ark']->id,
            'env_variable' => 'ARK_ADMIN_PASSWORD',
        ], [
            'name' => 'Admin Password',
            'description' => 'If specified, players must provide this password (via the in-game console) to gain access to administrator commands on the server.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'alpha_dash|between:1,100',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['ark']->id,
            'env_variable' => 'SERVER_MAX_PLAYERS',
        ], [
            'name' => 'Maximum Players',
            'description' => 'Specifies the maximum number of players that can play on the server simultaneously.',
            'default_value' => 20,
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|numeric|digits_between:1,4',
        ]);
    }

    private function addCSGOVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['csgo']->id,
            'env_variable' => 'SRCDS_MAP',
        ], [
            'name' => 'Map',
            'description' => 'The default map for the server.',
            'default_value' => 'de_dust2',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string|alpha_dash',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['csgo']->id,
            'env_variable' => 'STEAM_ACC',
        ], [
            'name' => 'Steam Account Token',
            'description' => 'The Steam Account Token required for the server to be displayed publicly.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string|alpha_num|size:32',
        ]);
    }

    private function addGMODVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['gmod']->id,
            'env_variable' => 'SRCDS_MAP',
        ], [
            'name' => 'Map',
            'description' => 'The default map for the server.',
            'default_value' => 'gm_flatgrass',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string|alpha_dash',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['gmod']->id,
            'env_variable' => 'STEAM_ACC',
        ], [
            'name' => 'Steam Account Token',
            'description' => 'The Steam Account Token required for the server to be displayed publicly.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string|alpha_num|size:32',
        ]);
    }

    private function addCustomVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['source']->id,
            'env_variable' => 'SRCDS_APPID',
        ], [
            'name' => 'Game ID',
            'description' => 'The ID corresponding to the game to download and run using SRCDS.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|numeric|digits_between:1,6',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['source']->id,
            'env_variable' => 'SRCDS_GAME',
        ], [
            'name' => 'Game Name',
            'description' => 'The name corresponding to the game to download and run using SRCDS.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|alpha_dash|between:1,100',
        ]);
    }
}
