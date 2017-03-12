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

class TerrariaServiceTableSeeder extends Seeder
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
            'folder' => 'terraria',
        ], [
            'name' => 'Terraria',
            'description' => 'Terraria is a land of adventure! A land of mystery! A land that\'s yours to shape, defend, and enjoy. Your options in Terraria are limitless. Are you an action gamer with an itchy trigger finger? A master builder? A collector? An explorer? There\'s something for everyone.',
            'startup' => 'mono TerrariaServer.exe -port {{SERVER_PORT}} -autocreate 2 -worldname World',
        ]);
    }

    private function addCoreOptions()
    {
        $this->option['tshock'] = ServiceOption::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'tshock',
        ], [
            'name' => 'Terraria Server (TShock)',
            'description' => 'TShock is a server modification for Terraria, written in C#, and based upon the Terraria Server API. It uses JSON for configuration management, and offers several features not present in the Terraria Server normally.',
            'docker_image' => 'quay.io/pterodactyl/terraria:tshock',
            'config_startup' => '{"userInteraction": [ "You need to agree to the EULA"]}',
            'config_startup' => '{"done": "Type \'help\' for a list of commands", "userInteraction": []}',
            'config_files' => '{"tshock/config.json":{"parser": "json", "find":{"ServerPort": "{{server.build.default.port}}", "MaxSlots": "{{server.build.env.MAX_SLOTS}}"}}}',
            'config_logs' => '{"custom": false, "location": "ServerLog.txt"}',
            'config_stop' => 'exit',
            'startup' => null,
        ]);
    }

    private function addVariables()
    {
        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['tshock']->id,
            'env_variable' => 'T_VERSION',
        ], [
            'name' => 'TShock Version',
            'description' => 'Which version of TShock to install and use.',
            'default_value' => '4.3.22',
            'user_viewable' => 1,
            'user_editable' => 1,
            'required' => 1,
            'rules' => 'required|regex:/^([0-9_\.-]{5,10})$/',
        ]);

        ServiceVariable::updateOrCreate([
            'option_id' => $this->option['tshock']->id,
            'env_variable' => 'MAX_SLOTS',
        ], [
            'name' => 'Maximum Slots',
            'description' => 'Total number of slots to allow on the server.',
            'default_value' => 20,
            'user_viewable' => 1,
            'user_editable' => 0,
            'required' => 1,
            'rules' => 'required|numeric|digits_between:1,3',
        ]);
    }
}
