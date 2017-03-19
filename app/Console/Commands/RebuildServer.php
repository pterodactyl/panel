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

namespace Pterodactyl\Console\Commands;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Console\Command;

class RebuildServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:rebuild
                            {--all}
                            {--node= : Id of node to rebuild all servers on.}
                            {--server= : UUID of server to rebuild.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild docker containers for a server or multiple servers.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('all')) {
            $servers = Server::all();
        } elseif ($this->option('node')) {
            $servers = Server::where('node_id', $this->option('node'))->get();
        } elseif ($this->option('server')) {
            $servers = Server::where('id', $this->option('server'))->get();
        } else {
            $this->error('You must pass a flag to determine which server(s) to rebuild.');

            return;
        }

        $servers->load('node', 'service', 'option.variables', 'pack');

        $this->line('Beginning processing, do not exit this script.');
        $bar = $this->output->createProgressBar(count($servers));
        $results = collect([]);
        foreach ($servers as $server) {
            try {
                $environment = $server->option->variables->map(function ($item, $key) use ($server) {
                    $display = $server->variables->where('variable_id', $item->id)->pluck('variable_value')->first();

                    return [
                        'variable' => $item->env_variable,
                        'value' => (! is_null($display)) ? $display : $item->default_value,
                    ];
                });

                $server->node->guzzleClient([
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $server->node->daemonSecret,
                ])->request('PATCH', '/server', [
                    'json' => [
                        'build' => [
                            'image' => $server->image,
                            'env|overwrite' => $environment->pluck('value', 'variable')->merge(['STARTUP' => $server->startup]),
                        ],
                        'service' => [
                            'type' => $server->service->folder,
                            'option' => $server->option->tag,
                            'pack' => ! is_null($server->pack) ? $server->pack->uuid : null,
                        ],
                    ],
                ]);

                $results = $results->merge([
                    $server->uuid => [
                        'status' => 'info',
                        'messages' => [
                                '[✓] Processed rebuild request for ' . $server->uuid,
                        ],
                    ],
                ]);
            } catch (\Exception $ex) {
                $results = $results->merge([
                    $server->uuid => [
                        'status' => 'error',
                        'messages' => [
                            '[✗] Failed to process rebuild request for ' . $server->uuid,
                            $ex->getMessage(),
                        ],
                    ],
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $console = $this;

        $this->line("\n");
        $results->each(function ($item, $key) use ($console) {
            foreach ($item['messages'] as $line) {
                $console->{$item['status']}($line);
            }
        });
        $this->line("\nCompleted rebuild command processing.");
    }
}
