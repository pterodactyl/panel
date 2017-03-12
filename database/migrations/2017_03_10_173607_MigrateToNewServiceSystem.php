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
use Pterodactyl\Models\Service;
use Pterodactyl\Models\ServiceOption;
use Illuminate\Database\Migrations\Migration;

class MigrateToNewServiceSystem extends Migration
{
    protected $services;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->services = Service::where('author', 'ptrdctyl-v040-11e6-8b77-86f30ca893d3')->get();

        $this->minecraft();
        $this->srcds();
        $this->terraria();
        $this->voice();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Not doing reversals right now...
    }

    public function minecraft()
    {
        $service = $this->services->where('folder', 'minecraft')->first();
        if (! $service) {
            return;
        }

        // Set New Default Startup
        $service->startup = 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}';

        DB::transaction(function () use ($service) {
            $options = ServiceOption::where('service_id', $service->id)->get();
            $options->each(function ($item) use ($options) {
                switch ($item->tag) {
                    case 'vanilla':
                        $item->config_startup = '{"done": ")! For help, type ", "userInteraction": [ "Go to eula.txt for more info."]}';
                        $item->config_files = '{"server.properties":{"parser": "properties", "find":{"server-ip": "0.0.0.0", "enable-query": "true", "server-port": "{{server.build.default.port}}", "query.port": "{{server.build.default.port}}"}}}';
                        $item->config_logs = '{"custom": false, "location": "logs/latest.log"}';
                        $item->config_stop = 'stop';
                        break;
                    case 'spigot':
                        $item->startup = null;
                        $item->config_from = $options->where('tag', 'vanilla')->pluck('id')->pop();
                        $item->config_files = '{"spigot.yml":{"parser": "yaml", "find":{"settings.restart-on-crash": "false"}}}';
                        break;
                    case 'bungeecord':
                        $item->config_startup = '{"done": "Listening on ", "userInteraction": [ "Listening on /0.0.0.0:25577"]}';
                        $item->config_files = '{"config.yml":{"parser": "yaml", "find":{"listeners[0].query_enabled": true, "listeners[0].query_port": "{{server.build.default.port}}", "listeners[0].host": "0.0.0.0:{{server.build.default.port}}", "servers.*.address":{"127.0.0.1": "{{config.docker.interface}}", "localhost": "{{config.docker.interface}}"}}}}';
                        $item->config_logs = '{"custom": false, "location": "proxy.log.0"}';
                        $item->config_stop = 'end';
                        break;
                    case 'sponge':
                        $item->startup = null;
                        $item->config_from = $options->where('tag', 'vanilla')->pluck('id')->pop();
                        $item->config_startup = '{"userInteraction": [ "You need to agree to the EULA"]}';
                        break;
                    default:
                        break;
                }

                $item->save();
            });

            $service->save();
        });
    }

    public function srcds()
    {
        $service = $this->services->where('folder', 'srcds')->first();
        if (! $service) {
            return;
        }

        $service->startup = './srcds_run -game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +ip 0.0.0.0 -strictportbind -norestart';

        DB::transaction(function () use ($service) {
            $options = ServiceOption::where('service_id', $service->id)->get();
            $options->each(function ($item) use ($options) {
                if ($item->tag === 'srcds' && $item->name === 'Insurgency') {
                    $item->tag = 'insurgency';
                } elseif ($item->tag === 'srcds' && $item->name === 'Team Fortress 2') {
                    $item->tag = 'tf2';
                } elseif ($item->tag === 'srcds' && $item->name === 'Custom Source Engine Game') {
                    $item->tag = 'source';
                }

                switch ($item->tag) {
                    case 'source':
                        $item->config_startup = '{"done": "Assigned anonymous gameserver", "userInteraction": []}';
                        $item->config_files = '{}';
                        $item->config_logs = '{"custom": true, "location": "logs/latest.log"}';
                        $item->config_stop = 'quit';
                        break;
                    case 'insurgency':
                    case 'tf2':
                        $item->startup = './srcds_run -game {{SRCDS_GAME}} -console -port {{SERVER_PORT}} +map {{SRCDS_MAP}} +ip 0.0.0.0 -strictportbind -norestart';
                        $item->config_from = $options->where('name', 'Custom Source Engine Game')->pluck('id')->pop();
                        break;
                    case 'ark':
                        $item->startup = './ShooterGame/Binaries/Linux/ShooterGameServer TheIsland?listen?ServerPassword={{ARK_PASSWORD}}?ServerAdminPassword={{ARK_ADMIN_PASSWORD}}?Port={{SERVER_PORT}}?MaxPlayers={{SERVER_MAX_PLAYERS}}';
                        $item->config_from = $options->where('name', 'Custom Source Engine Game')->pluck('id')->pop();
                        $item->config_startup = '{"done": "Setting breakpad minidump AppID"}';
                        $item->config_stop = '^C';
                        break;
                    default:
                        break;
                }

                $item->save();
            });

            $service->save();
        });
    }

    public function terraria()
    {
        $service = $this->services->where('folder', 'terraria')->first();
        if (! $service) {
            return;
        }

        $service->startup = 'mono TerrariaServer.exe -port {{SERVER_PORT}} -autocreate 2 -worldname World';

        DB::transaction(function () use ($service) {
            $options = ServiceOption::where('service_id', $service->id)->get();
            $options->each(function ($item) use ($options) {
                switch ($item->tag) {
                    case 'tshock':
                        $item->startup = null;
                        $item->config_startup = '{"done": "Type \'help\' for a list of commands", "userInteraction": []}';
                        $item->config_files = '{"tshock/config.json":{"parser": "json", "find":{"ServerPort": "{{server.build.default.port}}", "MaxSlots": "{{server.build.env.MAX_SLOTS}}"}}}';
                        $item->config_logs = '{"custom": false, "location": "ServerLog.txt"}';
                        $item->config_stop = 'exit';
                        break;
                    default:
                        break;
                }

                $item->save();
            });

            $service->save();
        });
    }

    public function voice()
    {
        $service = $this->services->where('folder', 'voice')->first();
        if (! $service) {
            return;
        }

        $service->startup = null;

        DB::transaction(function () use ($service) {
            $options = ServiceOption::where('service_id', $service->id)->get();
            $options->each(function ($item) use ($options) {
                switch ($item->tag) {
                    case 'mumble':
                        $item->startup = './murmur.x86 -fg';
                        $item->config_startup = '{"done": "Server listening on", "userInteraction": [ "Generating new server certificate"]}';
                        $item->config_files = '{"murmur.ini":{"parser": "ini", "find":{"logfile": "murmur.log", "port": "{{server.build.default.port}}", "host": "0.0.0.0", "users": "{{server.build.env.MAX_USERS}}"}}}';
                        $item->config_logs = '{"custom": true, "location": "logs/murmur.log"}';
                        $item->config_stop = '^C';
                        break;
                    case 'ts3':
                        $item->startup = './ts3server_minimal_runscript.sh default_voice_port={{SERVER_PORT}} query_port={{SERVER_PORT}}';
                        $item->config_startup = '{"done": "listening on 0.0.0.0:", "userInteraction": []}';
                        $item->config_files = '{"ts3server.ini":{"parser": "ini", "find":{"default_voice_port": "{{server.build.default.port}}", "voice_ip": "0.0.0.0", "query_port": "{{server.build.default.port}}", "query_ip": "0.0.0.0"}}}';
                        $item->config_logs = '{"custom": true, "location": "logs/ts3.log"}';
                        $item->config_stop = '^C';
                        break;
                    default:
                        break;
                }

                $item->save();
            });

            $service->save();
        });
    }
}
