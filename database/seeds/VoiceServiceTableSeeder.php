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

class VoiceServiceTableSeeder extends Seeder
{
    /**
     * The core service ID.
     *
     * @var Service
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
            'folder' => 'voice',
        ], [
            'name' => 'Voice Servers',
            'description' => 'Voice servers such as Mumble and Teamspeak 3.',
            'startup' => '',
        ]);
    }

    private function addCoreOptions()
    {
        $this->option['mumble'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'mumble',
        ], [
            'name' => 'Mumble Server',
            'description' => 'Mumble is an open source, low-latency, high quality voice chat software primarily intended for use while gaming.',
            'docker_image' => 'quay.io/pterodactyl/voice:mumble',
            'config_startup' => '{"done": "Server listening on", "userInteraction": [ "Generating new server certificate"]}',
            'config_files' => '{"murmur.ini":{"parser": "ini", "find":{"logfile": "murmur.log", "port": "{{server.build.default.port}}", "host": "0.0.0.0", "users": "{{server.build.env.MAX_USERS}}"}}}',
            'config_logs' => '{"custom": true, "location": "logs/murmur.log"}',
            'config_stop' => '^C',
            'config_from' => null,
            'startup' => './murmur.x86 -fg',
        ]);

        $this->option['ts3'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'ts3',
        ], [
            'name' => 'Teamspeak3 Server',
            'description' => 'VoIP software designed with security in mind, featuring crystal clear voice quality, endless customization options, and scalabilty up to thousands of simultaneous users.',
            'docker_image' => 'quay.io/pterodactyl/voice:ts3',
            'config_startup' => '{"done": "listening on 0.0.0.0:", "userInteraction": []}',
            'config_files' => '{"ts3server.ini":{"parser": "ini", "find":{"default_voice_port": "{{server.build.default.port}}", "voice_ip": "0.0.0.0", "query_port": "{{server.build.default.port}}", "query_ip": "0.0.0.0"}}}',
            'config_logs' => '{"custom": true, "location": "logs/ts3.log"}',
            'config_stop' => '^C',
            'config_from' => null,
            'startup' => './ts3server_minimal_runscript.sh default_voice_port={{SERVER_PORT}} query_port={{SERVER_PORT}}',
        ]);
    }

    private function addVariables()
    {
        ServiceVariable::updateOrCreate([
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

        ServiceVariable::updateOrCreate([
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

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['ts3']->id,
            'env_variable' => 'T_VERSION',
        ], [
            'name' => 'Server Version',
            'description' => 'The version of Teamspeak 3 to use when running the server.',
            'default_value' => '3.1.1.1',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([0-9_\.-]{5,10})$/',
        ]);
    }
}
