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

class TerrariaServiceTableSeeder extends Seeder
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
            'folder' => 'terraria',
        ], [
            'name' => 'Terraria',
            'description' => 'Terraria is a land of adventure! A land of mystery! A land that\'s yours to shape, defend, and enjoy. Your options in Terraria are limitless. Are you an action gamer with an itchy trigger finger? A master builder? A collector? An explorer? There\'s something for everyone.',
            'startup' => 'mono TerrariaServer.exe -port {{SERVER_PORT}} -autocreate 2 -worldname World',
            'index_file' => $this->getIndexScript(),
        ]);
    }

    private function addCoreOptions()
    {
        $script = <<<'EOF'
#!/bin/ash
# TShock Installation Script
#
# Server Files: /mnt/server
apk update
apk add curl unzip

cd /tmp

curl -sSLO https://github.com/NyxStudios/TShock/releases/download/v${T_VERSION}/tshock_${T_VERSION}.zip

unzip -o tshock_${T_VERSION}.zip -d /mnt/server
EOF;

        $this->option['tshock'] = Egg::updateOrCreate([
            'service_id' => $this->service->id,
            'tag' => 'tshock',
        ], [
            'name' => 'Terraria Server (TShock)',
            'description' => 'TShock is a server modification for Terraria, written in C#, and based upon the Terraria Server API. It uses JSON for configuration management, and offers several features not present in the Terraria Server normally.',
            'docker_image' => 'quay.io/pterodactyl/core:mono',
            'config_startup' => '{"userInteraction": [ "You need to agree to the EULA"]}',
            'config_startup' => '{"done": "Type \'help\' for a list of commands", "userInteraction": []}',
            'config_files' => '{"tshock/config.json":{"parser": "json", "find":{"ServerPort": "{{server.build.default.port}}", "MaxSlots": "{{server.build.env.MAX_SLOTS}}"}}}',
            'config_logs' => '{"custom": false, "location": "ServerLog.txt"}',
            'config_stop' => 'exit',
            'startup' => null,
            'script_install' => $script,
        ]);
    }

    private function addVariables()
    {
        EggVariable::updateOrCreate([
            'option_id' => $this->option['tshock']->id,
            'env_variable' => 'T_VERSION',
        ], [
            'name' => 'TShock Version',
            'description' => 'Which version of TShock to install and use.',
            'default_value' => '4.3.22',
            'user_viewable' => 1,
            'user_editable' => 1,
            'rules' => 'required|regex:/^([0-9_\.-]{5,10})$/',
        ]);

        EggVariable::updateOrCreate([
            'option_id' => $this->option['tshock']->id,
            'env_variable' => 'MAX_SLOTS',
        ], [
            'name' => 'Maximum Slots',
            'description' => 'Total number of slots to allow on the server.',
            'default_value' => 20,
            'user_viewable' => 1,
            'user_editable' => 0,
            'rules' => 'required|numeric|digits_between:1,3',
        ]);
    }
}
