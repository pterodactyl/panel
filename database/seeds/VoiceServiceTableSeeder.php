<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>.
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

class VoiceServiceTableSeeder extends Seeder
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
            'name' => 'Voice Servers',
            'description' => 'Voice servers such as Mumble and Teamspeak 3.',
            'file' => 'voice',
            'executable' => '',
            'startup' => '',
        ]);
    }

    private function addCoreOptions()
    {
        $this->option['mumble'] = Models\ServiceOptions::create([
            'parent_service' => $this->service->id,
            'name' => 'Mumble Server',
            'description' => 'Mumble is an open source, low-latency, high quality voice chat software primarily intended for use while gaming.',
            'tag' => 'mumble',
            'docker_image' => 'quay.io/pterodactyl/voice:mumble',
            'executable' => './murmur.x86',
            'startup' => '-fg',
        ]);

        $this->option['ts3'] = Models\ServiceOptions::create([
            'parent_service' => $this->service->id,
            'name' => 'Teamspeak3 Server',
            'description' => 'VoIP software designed with security in mind, featuring crystal clear voice quality, endless customization options, and scalabilty up to thousands of simultaneous users.',
            'tag' => 'ts3',
            'docker_image' => 'quay.io/pterodactyl/voice:ts3',
            'executable' => './ts3server_minimal_runscript.sh',
            'startup' => 'default_voice_port={{SERVER_PORT}} query_port={{SERVER_PORT}}',
        ]);
    }

    private function addVariables()
    {
        Models\ServiceVariables::create([
            'option_id' => $this->option['mumble']->id,
            'name' => 'Maximum Users',
            'description' => 'Maximum concurrent users on the mumble server.',
            'env_variable' => 'MAX_USERS',
            'default_value' => '100',
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'regex' => '/^(\d){1,6}$/',
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['mumble']->id,
            'name' => 'Server Version',
            'description' => 'Version of Mumble Server to download and use.',
            'env_variable' => 'MUMBLE_VERSION',
            'default_value' => '1.2.16',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^([0-9_\.-]{5,8})$/',
        ]);

        Models\ServiceVariables::create([
            'option_id' => $this->option['ts3']->id,
            'name' => 'Server Version',
            'description' => 'The version of Teamspeak 3 to use when running the server.',
            'env_variable' => 'T_VERSION',
            'default_value' => '3.0.13.4',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'regex' => '/^([0-9_\.-]{5,10})$/',
        ]);
    }
}
