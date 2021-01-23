<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Environment;

use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class AppSettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    public const ALLOWED_CACHE_DRIVERS = [
        'redis' => 'Redis (recommended)',
        'memcached' => 'Memcached',
        'file' => 'Filesystem',
    ];

    public const ALLOWED_SESSION_DRIVERS = [
        'redis' => 'Redis (recommended)',
        'memcached' => 'Memcached',
        'database' => 'MySQL Database',
        'file' => 'Filesystem',
        'cookie' => 'Cookie',
    ];

    public const ALLOWED_QUEUE_DRIVERS = [
        'redis' => 'Redis (recommended)',
        'database' => 'MySQL Database',
        'sync' => 'Sync',
    ];

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
                            {--new-salt : Whether or not to generate a new salt for Hashids.}
                            {--author= : The email that services created on this instance should be linked to.}
                            {--url= : The URL that this Panel is running on.}
                            {--timezone= : The timezone to use for Panel times.}
                            {--cache= : The cache driver backend to use.}
                            {--session= : The session driver backend to use.}
                            {--queue= : The queue driver backend to use.}
                            {--redis-host= : Redis host to use for connections.}
                            {--redis-pass= : Password used to connect to redis.}
                            {--redis-port= : Port to connect to redis over.}
                            {--settings-ui= : Enable or disable the settings UI.}';

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * AppSettingsCommand constructor.
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
        if (empty($this->config->get('hashids.salt')) || $this->option('new-salt')) {
            $this->variables['HASHIDS_SALT'] = str_random(20);
        }

        $this->output->comment(trans('command/messages.environment.app.author_help'));
        $this->variables['APP_SERVICE_AUTHOR'] = $this->option('author') ?? $this->ask(
            trans('command/messages.environment.app.author'),
            $this->config->get('pterodactyl.service.author', 'unknown@unknown.com')
        );

        $this->output->comment(trans('command/messages.environment.app.app_url_help'));
        $this->variables['APP_URL'] = $this->option('url') ?? $this->ask(
            trans('command/messages.environment.app.app_url'),
            $this->config->get('app.url', 'http://example.org')
        );

        $this->output->comment(trans('command/messages.environment.app.timezone_help'));
        $this->variables['APP_TIMEZONE'] = $this->option('timezone') ?? $this->anticipate(
            trans('command/messages.environment.app.timezone'),
            DateTimeZone::listIdentifiers(DateTimeZone::ALL),
            $this->config->get('app.timezone')
        );

        $selected = $this->config->get('cache.default', 'redis');
        $this->variables['CACHE_DRIVER'] = $this->option('cache') ?? $this->choice(
            trans('command/messages.environment.app.cache_driver'),
            self::ALLOWED_CACHE_DRIVERS,
            array_key_exists($selected, self::ALLOWED_CACHE_DRIVERS) ? $selected : null
        );

        $selected = $this->config->get('session.driver', 'redis');
        $this->variables['SESSION_DRIVER'] = $this->option('session') ?? $this->choice(
            trans('command/messages.environment.app.session_driver'),
            self::ALLOWED_SESSION_DRIVERS,
            array_key_exists($selected, self::ALLOWED_SESSION_DRIVERS) ? $selected : null
        );

        $selected = $this->config->get('queue.default', 'redis');
        $this->variables['QUEUE_CONNECTION'] = $this->option('queue') ?? $this->choice(
            trans('command/messages.environment.app.queue_driver'),
            self::ALLOWED_QUEUE_DRIVERS,
            array_key_exists($selected, self::ALLOWED_QUEUE_DRIVERS) ? $selected : null
        );

        if (!is_null($this->option('settings-ui'))) {
            $this->variables['APP_ENVIRONMENT_ONLY'] = $this->option('settings-ui') == 'true' ? 'false' : 'true';
        } else {
            $this->variables['APP_ENVIRONMENT_ONLY'] = $this->confirm(trans('command/messages.environment.app.settings'), true) ? 'false' : 'true';
        }

        // Make sure session cookies are set as "secure" when using HTTPS
        if (strpos($this->variables['APP_URL'], 'https://') === 0) {
            $this->variables['SESSION_SECURE_COOKIE'] = 'true';
        }

        $this->checkForRedis();
        $this->writeToEnvironment($this->variables);

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
            trans('command/messages.environment.app.redis_host'),
            $this->config->get('database.redis.default.host')
        );

        $askForRedisPassword = true;
        if (!empty($this->config->get('database.redis.default.password'))) {
            $this->variables['REDIS_PASSWORD'] = $this->config->get('database.redis.default.password');
            $askForRedisPassword = $this->confirm(trans('command/messages.environment.app.redis_pass_defined'));
        }

        if ($askForRedisPassword) {
            $this->output->comment(trans('command/messages.environment.app.redis_pass_help'));
            $this->variables['REDIS_PASSWORD'] = $this->option('redis-pass') ?? $this->output->askHidden(
                trans('command/messages.environment.app.redis_password')
            );
        }

        if (empty($this->variables['REDIS_PASSWORD'])) {
            $this->variables['REDIS_PASSWORD'] = 'null';
        }

        $this->variables['REDIS_PORT'] = $this->option('redis-port') ?? $this->ask(
            trans('command/messages.environment.app.redis_port'),
            $this->config->get('database.redis.default.port')
        );
    }
}
