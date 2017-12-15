<?php

namespace Pterodactyl\Console\Commands\Cloud;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Pack;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Console\Command;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\DatabaseHost;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class UsagePingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:cloud:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a usage ping';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Create a new command instance.
     *
     * @param \GuzzleHttp\Client    $client
     */
    public function __construct(GuzzleClient $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->client->request('POST', 'https://pterodactyl.cloud/api/usageData/panel', [
                'timeout' => config('pterodactyl.guzzle.timeout'),
                'connect_timeout' => config('pterodactyl.guzzle.connect_timeout'),
                'json' => [
                    'uuid' => config('pterodactyl.cloud.uuid'),
                    'hostname' => config('app.url'),
                    'appAuthor' => config('pterodactyl.service.author'),
                    'version' => config('app.version'),
                    'features' => [
                        'newDaemon' => config('pterodactyl.daemon.use_new_daemon'),
                        'recaptcha' => config('recaptcha.enabled'),
                        'theme' => config('themes.active'),
                    ],
                    'usage' => [
                        'databases' => Database::count(),
                        'databaseHosts' => DatabaseHost::count(),
                        'eggs' => Egg::count(),
                        'locations' => Location::count(),
                        'nests' => Nest::count(),
                        'nodes' => Node::count(),
                        'packs' => Pack::count(),
                        'schedules' => Schedule::count(),
                        'servers' => Server::count(),
                        'users' => User::count(),
                        'subusers' => Subuser::count(),
                    ],
                    'drivers' => [
                        'cache' => config('cache.default'),
                        'session' => config('session.driver'),
                        'mail' => config('mail.driver'),
                        'queue' => config('queue.default'),
                    ],
                ],
            ]);
        } catch (RequestException $e) {
            // do nothing if this fails
        }
    }
}
