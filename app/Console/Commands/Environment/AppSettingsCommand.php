<?php
/*
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

namespace Pterodactyl\Console\Commands\Environment;

use Ramsey\Uuid\Uuid;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class AppSettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    protected $command;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var string
     */
    protected $description = 'Configure basic environment settings for the Panel.';

    /**
     * @var string
     */
    protected $signature = 'p:environment:setup
                            {--url= : The URL that this Panel is running on.}
                            {--timezone= : The timezone to use for Panel times.}
                            {--cache= : The cache driver backend to use.}
                            {--session= : The session driver backend to use.}
                            {--redis-host= : Redis host to use for connections.}
                            {--redis-pass= : Password used to connect to redis.}
                            {--redis-port= : Port to connect to redis over.}';

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * AppSettingsCommand constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\Console\Kernel    $command
     */
    public function __construct(ConfigRepository $config, Kernel $command)
    {
        parent::__construct();

        $this->command = $command;
        $this->config = $config;
    }

    /**
     * Handle command execution.
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function handle()
    {
        if (is_null($this->config->get('pterodactyl.service.author'))) {
            $this->variables['SERVICE_AUTHOR'] = Uuid::uuid4()->toString();
        }

        $this->output->comment(trans('command/messages.environment.app.app_url_help'));
        $this->variables['APP_URL'] = $this->option('url') ?? $this->ask(
            trans('command/messages.environment.app.app_url'), $this->config->get('app.url', 'http://example.org')
        );

        $this->output->comment(trans('command/messages.environment.app.timezone_help'));
        $this->variables['APP_TIMEZONE'] = $this->option('timezone') ?? $this->ask(
            trans('command/messages.environment.app.timezone'), $this->config->get('app.timezone')
        );

        $this->variables['CACHE_DRIVER'] = $this->option('cache') ?? $this->choice(
            trans('command/messages.environment.app.cache_driver'), [
                'redis' => 'Redis (recommended)',
                'memcached' => 'Memcached',
            ], $this->config->get('cache.default', 'redis')
        );

        $this->variables['SESSION_DRIVER'] = $this->option('session') ?? $this->choice(
            trans('command/messages.environment.app.session_driver'), [
                'redis' => 'Redis (recommended)',
                'memcached' => 'Memcached',
                'mysql' => 'MySQL Database',
                'file' => 'Filesystem',
                'cookie' => 'Cookie',
            ], $this->config->get('session.driver', 'redis')
        );

        $this->variables['QUEUE_DRIVER'] = $this->option('session') ?? $this->choice(
            trans('command/messages.environment.app.session_driver'), [
                'redis' => 'Redis (recommended)',
                'database' => 'MySQL Database',
                'sync' => 'Sync',
            ], $this->config->get('queue.driver', 'redis')
        );

        $this->checkForRedis();
        $this->writeToEnvironment($this->variables);

        $this->command->call('config:cache');
        $this->info($this->command->output());
    }

    /**
     * Check if redis is selected, if so, request connection details and verify them.
     */
    private function checkForRedis()
    {
        $items = collect($this->variables)->filter(function ($item) {
            return $item === 'redis';
        });

        // Redis was not selected, no need to continue.
        if (count($items) === 0) {
            return;
        }

        $this->output->note(trans('command/messages.environment.app.using_redis'));
        $this->variables['REDIS_HOST'] = $this->option('redis-host') ?? $this->ask(
            trans('command/messages.environment.app.redis_host'), $this->config->get('database.redis.default.host')
        );

        $askForRedisPassword = true;
        if (! empty($this->config->get('database.redis.default.password'))) {
            $this->variables['REDIS_PASSWORD'] = $this->config->get('database.redis.default.password');
            $askForRedisPassword = $this->confirm(trans('command/messages.environment.app.redis_pass_defined'));
        }

        if ($askForRedisPassword) {
            $this->output->comment(trans('command/messages.environment.app.redis_pass_help'));
            $this->variables['REDIS_PASSWORD'] = $this->option('redis-pass') ?? $this->output->askHidden(
                trans('command/messages.environment.app.redis_password'), function () {
                    return true;
                }
            );
        }

        $this->variables['REDIS_PORT'] = $this->option('redis-port') ?? $this->ask(
            trans('command/messages.environment.app.redis_port'), $this->config->get('database.redis.default.port')
        );
    }
}
