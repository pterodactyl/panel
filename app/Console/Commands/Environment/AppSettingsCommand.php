<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Environment;

use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;

#[Description('Configure basic environment settings for the Panel.')]
#[Signature('p:environment:setup
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
                            {--settings-ui= : Enable or disable the settings UI.}
                            {--telemetry= : Enable or disable anonymous telemetry.}')]
class AppSettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    public const array CACHE_DRIVERS = [
        'redis' => 'Redis (recommended)',
        'memcached' => 'Memcached',
        'file' => 'Filesystem',
    ];

    public const array SESSION_DRIVERS = [
        'redis' => 'Redis (recommended)',
        'memcached' => 'Memcached',
        'database' => 'MySQL Database',
        'file' => 'Filesystem',
        'cookie' => 'Cookie',
    ];

    public const array QUEUE_DRIVERS = [
        'redis' => 'Redis (recommended)',
        'database' => 'MySQL Database',
        'sync' => 'Sync',
    ];

    /** @var array<string, EnvironmentValue> */
    protected array $variables = [];

    /**
     * AppSettingsCommand constructor.
     */
    public function __construct(private Kernel $console)
    {
        parent::__construct();
    }

    /**
     * Handle command execution.
     *
     * @throws PterodactylException
     */
    public function handle(): int
    {
        if (empty(config('hashids.salt')) || $this->option('new-salt')) {
            $this->variables['HASHIDS_SALT'] = Str::random(20);
        }

        $this->output->comment('Provide the email address that eggs exported by this Panel should be from. This should be a valid email address.');
        $this->variables['APP_SERVICE_AUTHOR'] = JsonValueGuard::string($this->option('author') ?? $this->ask(
            'Egg Author Email',
            JsonValueGuard::nullableString(config('pterodactyl.service.author', 'unknown@unknown.com'))
        ));

        if (! filter_var($this->variables['APP_SERVICE_AUTHOR'], FILTER_VALIDATE_EMAIL)) {
            $this->output->error('The service author email provided is invalid.');

            return 1;
        }

        $this->output->comment('The application URL MUST begin with https:// or http:// depending on if you are using SSL or not. If you do not include the scheme your emails and other content will link to the wrong location.');
        $this->variables['APP_URL'] = JsonValueGuard::string($this->option('url') ?? $this->ask(
            'Application URL',
            JsonValueGuard::nullableString(config('app.url', 'https://example.com'))
        ));

        $this->output->comment("The timezone should match one of PHP's supported timezones. If you are unsure, please reference https://php.net/manual/en/timezones.php.");
        $this->variables['APP_TIMEZONE'] = JsonValueGuard::string($this->option('timezone') ?? $this->anticipate(
            'Application Timezone',
            DateTimeZone::listIdentifiers(),
            JsonValueGuard::nullableString(config('app.timezone'))
        ));

        $selected = JsonValueGuard::string(config('cache.default', 'redis'));
        $cacheDriver = $this->option('cache') ?? $this->choice(
            'Cache Driver',
            self::CACHE_DRIVERS,
            array_key_exists($selected, self::CACHE_DRIVERS) ? $selected : null
        );
        JsonValueGuard::assertScalar($cacheDriver);
        $this->variables['CACHE_DRIVER'] = $this->stringValue($cacheDriver);

        $selected = JsonValueGuard::string(config('session.driver', 'redis'));
        $sessionDriver = $this->option('session') ?? $this->choice(
            'Session Driver',
            self::SESSION_DRIVERS,
            array_key_exists($selected, self::SESSION_DRIVERS) ? $selected : null
        );
        JsonValueGuard::assertScalar($sessionDriver);
        $this->variables['SESSION_DRIVER'] = $this->stringValue($sessionDriver);

        $selected = JsonValueGuard::string(config('queue.default', 'redis'));
        $queueDriver = $this->option('queue') ?? $this->choice(
            'Queue Driver',
            self::QUEUE_DRIVERS,
            array_key_exists($selected, self::QUEUE_DRIVERS) ? $selected : null
        );
        JsonValueGuard::assertScalar($queueDriver);
        $this->variables['QUEUE_CONNECTION'] = $this->stringValue($queueDriver);

        if (($this->option('settings-ui')) !== null) {
            $this->variables['APP_ENVIRONMENT_ONLY'] = $this->option('settings-ui') === 'true' ? 'false' : 'true';
        } else {
            $this->variables['APP_ENVIRONMENT_ONLY'] = $this->confirm('Enable UI based settings editor?', true) ? 'false' : 'true';
        }

        $this->output->comment('Please reference https://pterodactyl.io/docs/v2/panel/additional-configuration#telemetry for more detailed information regarding telemetry data and collection.');
        if (($this->option('telemetry')) !== null) {
            $this->variables['PTERODACTYL_TELEMETRY_ENABLED'] = $this->option('telemetry') === 'true' ? 'true' : 'false';
        } else {
            $this->variables['PTERODACTYL_TELEMETRY_ENABLED'] = $this->confirm(
                'Enable sending anonymous telemetry data?',
                JsonValueGuard::boolean(config('pterodactyl.telemetry.enabled', true))
            ) ? 'true' : 'false';
        }

        // Make sure session cookies are set as "secure" when using HTTPS
        if (str_starts_with($this->variables['APP_URL'], 'https://')) {
            $this->variables['SESSION_SECURE_COOKIE'] = 'true';
        }

        $this->checkForRedis();
        $this->writeToEnvironment($this->variables);

        $this->info($this->console->output());

        return 0;
    }

    /**
     * Check if redis is selected, if so, request connection details and verify them.
     */
    private function checkForRedis(): void
    {
        $items = collect($this->variables)->filter(fn (bool|float|int|string|null $item): bool => $item === 'redis');

        // Redis was not selected, no need to continue.
        if (count($items) === 0) {
            return;
        }

        $this->output->note("You've selected the Redis driver for one or more options, please provide valid connection information below. In most cases you can use the defaults provided unless you have modified your setup.");
        $this->variables['REDIS_HOST'] = JsonValueGuard::string($this->option('redis-host') ?? $this->ask(
            'Redis Host',
            JsonValueGuard::nullableScalarString(config('database.redis.default.host'))
        ));

        $askForRedisPassword = true;
        if (! empty(config('database.redis.default.password'))) {
            $this->variables['REDIS_PASSWORD'] = JsonValueGuard::scalar(config('database.redis.default.password'));
            $askForRedisPassword = $this->confirm('It seems a password is already defined for Redis, would you like to change it?');
        }

        if ($askForRedisPassword) {
            $this->output->comment('By default a Redis server instance has no password as it is running locally and inaccessible to the outside world. If this is the case, simply hit enter without entering a value.');
            $this->variables['REDIS_PASSWORD'] = JsonValueGuard::scalar($this->option('redis-pass') ?? $this->output->askHidden(
                'Redis Password'
            ));
        }

        if (empty($this->variables['REDIS_PASSWORD'])) {
            $this->variables['REDIS_PASSWORD'] = 'null';
        }

        $this->variables['REDIS_PORT'] = JsonValueGuard::scalar($this->option('redis-port') ?? $this->ask(
            'Redis Port',
            JsonValueGuard::nullableScalarString(config('database.redis.default.port'))
        ));
    }

    /** @param ApiScalar $value */
    private function stringValue(bool|float|int|string|null $value): string
    {
        throw_unless(is_string($value), InvalidArgumentException::class, 'Expected a single environment option value.');

        return $value;
    }
}
