<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Illuminate\Database\Seeder;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Traits\Services\CreatesServiceIndex;

class VoiceServiceTableSeeder extends Seeder
{
    use CreatesServiceIndex;

    /**
     * The core service ID.
     *
     * @var Nest
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
     */
    public function run()
    {
        $this->addCoreService();
        $this->addCoreOptions();
        $this->addVariables();
    }

    private function addCoreService()
    {
        $this->service = Nest::updateOrCreate([
            'author' => config('pterodactyl.service.core'),
            'folder' => 'voice',
        ], [
            'name' => 'Voice Servers',
            'description' => 'Voice servers such as Mumble and Teamspeak 3.',
            'startup' => '',
            'index_file' => $this->getIndexScript(),
        ]);
    }

    private function addCoreOptions()
    {
        $script = <<<'EOF'
#!/bin/ash
# Mumble Installation Script
#
# Server Files: /mnt/server
apk update
apk add tar curl

cd /tmp

curl -sSLO https://github.com/mumble-voip/mumble/releases/download/${MUMBLE_VERSION}/murmur-static_x86-${MUMBLE_VERSION}.tar.bz2

tar -xjvf murmur-static_x86-${MUMBLE_VERSION}.tar.bz2
cp -r murmur-static_x86-${MUMBLE_VERSION}/* /mnt/server
EOF;

        $this->option['mumble'] = Egg::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'mumble',
        ], [
            'name' => 'Mumble Server',
            'description' => 'Mumble is an open source, low-latency, high quality voice chat software primarily intended for use while gaming.',
            'docker_image' => 'quay.io/pterodactyl/core:glibc',
            'config_startup' => '{"done": "Server listening on", "userInteraction": [ "Generating new server certificate"]}',
            'config_files' => '{"murmur.ini":{"parser": "ini", "find":{"logfile": "murmur.log", "port": "{{server.build.default.port}}", "host": "0.0.0.0", "users": "{{server.build.env.MAX_USERS}}"}}}',
            'config_logs' => '{"custom": true, "location": "logs/murmur.log"}',
            'config_stop' => '^C',
            'config_from' => null,
            'startup' => './murmur.x86 -fg',
            'script_install' => $script,
        ]);

        $script = <<<'EOF'
#!/bin/ash
# TS3 Installation Script
#
# Server Files: /mnt/server
apk update
apk add tar curl

cd /tmp

curl -sSLO http://dl.4players.de/ts/releases/${TS_VERSION}/teamspeak3-server_linux_amd64-${TS_VERSION}.tar.bz2

tar -xjvf teamspeak3-server_linux_amd64-${TS_VERSION}.tar.bz2
cp -r teamspeak3-server_linux_amd64/* /mnt/server

echo "machine_id=
default_voice_port=${SERVER_PORT}
voice_ip=0.0.0.0
licensepath=
filetransfer_port=30033
filetransfer_ip=
query_port=${SERVER_PORT}
query_ip=0.0.0.0
query_ip_whitelist=query_ip_whitelist.txt
query_ip_blacklist=query_ip_blacklist.txt
dbplugin=ts3db_sqlite3
dbpluginparameter=
dbsqlpath=sql/
dbsqlcreatepath=create_sqlite/
dbconnections=10
logpath=logs
logquerycommands=0
dbclientkeepdays=30
logappend=0
query_skipbruteforcecheck=0" > /mnt/server/ts3server.ini
EOF;

        $this->option['ts3'] = Egg::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'ts3',
        ], [
            'name' => 'Teamspeak3 Server',
            'description' => 'VoIP software designed with security in mind, featuring crystal clear voice quality, endless customization options, and scalabilty up to thousands of simultaneous users.',
            'docker_image' => 'quay.io/pterodactyl/core:glibc',
            'config_startup' => '{"done": "listening on 0.0.0.0:", "userInteraction": []}',
            'config_files' => '{"ts3server.ini":{"parser": "ini", "find":{"default_voice_port": "{{server.build.default.port}}", "voice_ip": "0.0.0.0", "query_port": "{{server.build.default.port}}", "query_ip": "0.0.0.0"}}}',
            'config_logs' => '{"custom": true, "location": "logs/ts3.log"}',
            'config_stop' => '^C',
            'config_from' => null,
            'startup' => './ts3server_minimal_runscript.sh default_voice_port={{SERVER_PORT}} query_port={{SERVER_PORT}}',
            'script_install' => $script,
        ]);
    }

    private function addVariables()
    {
        EggVariable::updateOrCreate([
            'option_id' => $this->option['mumble']->id,
            'env_variable' => 'MAX_USERS',
        ], [
            'name' => 'Maximum Users',
            'description' => 'Maximum concurrent users on the mumble server.',
            'default_value' => 100,
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|numeric|digits_between:1,5',
        ]);

        EggVariable::updateOrCreate([
            'option_id' => $this->option['mumble']->id,
            'env_variable' => 'MUMBLE_VERSION',
        ], [
            'name' => 'Server Version',
            'description' => 'Version of Mumble Server to download and use.',
            'default_value' => '1.2.19',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([0-9_\.-]{5,8})$/',
        ]);

        EggVariable::updateOrCreate([
            'option_id' => $this->option['ts3']->id,
            'env_variable' => 'TS_VERSION',
        ], [
            'name' => 'Server Version',
            'description' => 'The version of Teamspeak 3 to use when running the server.',
            'default_value' => '3.0.13.7',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([0-9_\.-]{5,10})$/',
        ]);
    }
}
