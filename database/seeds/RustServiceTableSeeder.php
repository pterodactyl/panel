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

class RustServiceTableSeeder extends Seeder
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
        $this->service = Service::updateOrCreate([
            'author' => config('pterodactyl.service.core'),
            'folder' => 'rust',
        ], [
            'name' => 'Rust',
            'description' => 'The only aim in Rust is to survive. To do this you will need to overcome struggles such as hunger, thirst and cold. Build a fire. Build a shelter. Kill animals for meat. Protect yourself from other players, and kill them for meat. Create alliances with other players and form a town. Do whatever it takes to survive.',
            'startup' => './RustDedicated -batchmode +server.port {{SERVER_PORT}} +server.identity "rust" +rcon.port {{RCON_PORT}} +rcon.web true +server.hostname \"{{HOSTNAME}}\" +server.level \"{{LEVEL}}\" +server.description \"{{DESCRIPTION}}\" +server.url \"{{URL}}\" +server.headerimage \"{{SERVER_IMG}}\" +server.worldsize \"{{WORLD_SIZE}}\" +server.seed \"{{SEED}}\" +server.maxplayers {{MAX_PLAYERS}} +rcon.password \"{{RCON_PASS}}\" {{ADDITIONAL_ARGS}}',
            'index_file' => Service::defaultIndexFile(),
        ]);
    }

    private function addCoreOptions()
    {
        $script = <<<'EOF'
apt update
apt -y --no-install-recommends install curl lib32gcc1 ca-certificates

cd /tmp
curl -sSL -o steamcmd.tar.gz http://media.steampowered.com/installer/steamcmd_linux.tar.gz

mkdir -p /mnt/server/steam
tar -xzvf steamcmd.tar.gz -C /mnt/server/steam
cd /mnt/server/steam

chown -R root:root /mnt

export HOME=/mnt/server
./steamcmd.sh +login anonymous +force_install_dir /mnt/server +app_update 258550 +quit

mkdir -p /mnt/server/.steam/sdk32
cp -v linux32/steamclient.so ../.steam/sdk32/steamclient.so
EOF;

        $this->option['rustvanilla'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'rustvanilla',
        ], [
            'name' => 'Vanilla',
            'description' => 'Vanilla Rust server.',
            'docker_image' => 'quay.io/pterodactyl/core:rust',
            'config_startup' => '{"done": "Server startup complete", "userInteraction": []}',
            'config_files' => '{}',
            'config_logs' => '{"custom": false, "location": "latest.log"}',
            'config_stop' => 'quit',
            'config_from' => null,
            'startup' => null,
            'script_install' => $script,
            'script_entry' => 'bash',
            'script_container' => 'ubuntu:16.04',
        ]);


        $script = <<<'EOF'
apt update
apt -y --no-install-recommends install curl unzip lib32gcc1 ca-certificates

cd /tmp
curl -sSL -o steamcmd.tar.gz http://media.steampowered.com/installer/steamcmd_linux.tar.gz

mkdir -p /mnt/server/steam
tar -xzvf steamcmd.tar.gz -C /mnt/server/steam
cd /mnt/server/steam

chown -R root:root /mnt

export HOME=/mnt/server
./steamcmd.sh +login anonymous +force_install_dir /mnt/server +app_update 258550 +quit

cd /mnt/server
curl https://dl.bintray.com/oxidemod/builds/Oxide-Rust.zip > oxide.zip
unzip oxide.zip
rm oxide.zip
echo "This file is used to determine whether the server is an OxideMod server or not.
Do not delete this file or you may loose OxideMod auto updating from the server." > OXIDE_FLAG

mkdir -p /mnt/server/.steam/sdk32
cp -v /mnt/server/steam/linux32/steamclient.so /mnt/server/.steam/sdk32/steamclient.so
EOF;

        $this->option['rustoxide'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'rustoxide',
        ], [
            'name' => 'OxideMod',
            'description' => 'OxideMod Rust server.',
            'docker_image' => 'quay.io/pterodactyl/core:rust',
            'config_startup' => '{"done": "Server startup complete", "userInteraction": []}',
            'config_files' => '{}',
            'config_logs' => '{"custom": false, "location": "latest.log"}',
            'config_stop' => 'quit',
            'config_from' => null,
            'startup' => null,
            'script_install' => $script,
            'script_entry' => 'bash',
            'script_container' => 'ubuntu:16.04',
        ]);
    }

    private function addVariables()
    {
        $this->addVanillaVariables();
        $this->addOxideVariables();
    }

    private function addVanillaVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'HOSTNAME',
        ], [
            'name' => 'Server Name',
            'description' => 'The name of your server in the public server list.',
            'default_value' => 'A Rust Server',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'LEVEL',
        ], [
            'name' => 'Level',
            'description' => 'The world file for Rust to use.',
            'default_value' => 'Procedural Map',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'DESCRIPTION',
        ], [
            'name' => 'Description',
            'description' => 'The description under your server title. Commonly used for rules & info.',
            'default_value' => 'Powered by Pterodactyl',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'URL',
        ], [
            'name' => 'URL',
            'description' => 'The URL for your server. This is what comes up when clicking the "Visit Website" button.',
            'default_value' => 'http://pterodactyl.io',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'url',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'WORLD_SIZE',
        ], [
            'name' => 'World Size',
            'description' => 'The world size for a procedural map.',
            'default_value' => '3000',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|integer',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'SEED',
        ], [
            'name' => 'World Seed',
            'description' => 'The seed for a procedural map.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'present',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'MAX_PLAYERS',
        ], [
            'name' => 'Max Players',
            'description' => 'The maximum amount of players allowed in the server at once.',
            'default_value' => '40',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|integer',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'SERVER_IMG',
        ], [
            'name' => 'Server Header Image',
            'description' => 'The header image for the top of your server listing.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'url',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'RCON_PORT',
        ], [
            'name' => 'RCON Port',
            'description' => 'Port for RCON connections.',
            'default_value' => '8401',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|integer',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'RCON_PASS',
        ], [
            'name' => 'RCON Password',
            'description' => 'Remote console access password.',
            'default_value' => 'CHANGEME',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustvanilla']->id,
            'env_variable' => 'ADDITIONAL_ARGS',
        ], [
            'name' => 'Additional Arguments',
            'description' => 'Add additional startup parameters to the server.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'present',
        ]);
    }

    private function addOxideVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'HOSTNAME',
        ], [
            'name' => 'Server Name',
            'description' => 'The name of your server in the public server list.',
            'default_value' => 'A Rust Server',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'LEVEL',
        ], [
            'name' => 'Level',
            'description' => 'The world file for Rust to use.',
            'default_value' => 'Procedural Map',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'DESCRIPTION',
        ], [
            'name' => 'Description',
            'description' => 'The description under your server title. Commonly used for rules & info.',
            'default_value' => 'Powered by Pterodactyl',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|string',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'URL',
        ], [
            'name' => 'URL',
            'description' => 'The URL for your server. This is what comes up when clicking the "Visit Website" button.',
            'default_value' => 'http://pterodactyl.io',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'url',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'WORLD_SIZE',
        ], [
            'name' => 'World Size',
            'description' => 'The world size for a procedural map.',
            'default_value' => '3000',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|integer',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'SEED',
        ], [
            'name' => 'World Seed',
            'description' => 'The seed for a procedural map.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'present',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'MAX_PLAYERS',
        ], [
            'name' => 'Max Players',
            'description' => 'The maximum amount of players allowed in the server at once.',
            'default_value' => '40',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|integer',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'SERVER_IMG',
        ], [
            'name' => 'Server Header Image',
            'description' => 'The header image for the top of your server listing.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'url',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'RCON_PORT',
        ], [
            'name' => 'RCON Port',
            'description' => 'Port for RCON connections.',
            'default_value' => '8401',
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|integer',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'RCON_PASS',
        ], [
            'name' => 'RCON Password',
            'description' => 'Remote console access password.',
            'default_value' => 'CHANGEME',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['rustoxide']->id,
            'env_variable' => 'ADDITIONAL_ARGS',
        ], [
            'name' => 'Additional Arguments',
            'description' => 'Add additional startup parameters to the server.',
            'default_value' => '',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'present',
        ]);
    }
}
