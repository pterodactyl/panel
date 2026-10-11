<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Integration\Api\Snapshots;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Pterodactyl\Contracts\Eggs\ExportsEggs;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Facades\Hashids;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Tag;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Services\Eggs\EggCatalogService;
use Pterodactyl\Tests\Support\Fakes\FakeDatabaseHostGateway;
use Pterodactyl\Tests\Support\Fakes\FakeDynamicDatabaseConnection;
use Pterodactyl\Tests\TestCase;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;
use UnexpectedValueException;

/**
 * The deterministic world behind the response snapshots. Every model is created with an
 * explicit id, uuid, name and timestamp; every source of randomness the request path can
 * reach (UUIDs, Str::random(), Faker, the clock, the Hashids salt and Wings) is pinned, so
 * a snapshot changes only when the bytes an endpoint returns change.
 *
 * The tables are emptied inside the test transaction before the world is built: seeded eggs
 * carry the timestamp of the seeding run, and auto-increment counters are never rolled back,
 * so neither may leak into a snapshot.
 */
final class SnapshotWorld
{
    public const string URL = 'https://panel.example.test';

    public const string NOW = '2026-03-04 05:06:07';

    public const string CREATED = '2026-01-02 03:04:05';

    public const string UPDATED = '2026-02-03 04:05:06';

    public const int ADMIN = 1;

    public const int OWNER = 2;

    public const int HELPER = 3;

    public const int LIMITED = 4;

    public const int SPARE = 5;

    public const string ADMIN_UUID = '00000000-0000-4000-8000-000000000001';

    public const string OWNER_UUID = '00000000-0000-4000-8000-000000000002';

    public const string HELPER_UUID = '00000000-0000-4000-8000-000000000003';

    public const string LIMITED_UUID = '00000000-0000-4000-8000-000000000004';

    public const string SPARE_UUID = '00000000-0000-4000-8000-000000000005';

    public const string SURVIVAL = '5e7f0001-0000-4000-8000-000000000001';

    public const string CREATIVE = '5e7f0002-0000-4000-8000-000000000002';

    public const string LAB = '5e7f0003-0000-4000-8000-000000000003';

    public const string BACKUP_NIGHTLY = 'bacc0001-0000-4000-8000-000000000001';

    public const string BACKUP_FAILED = 'bacc0002-0000-4000-8000-000000000002';

    public const string BACKUP_RUNNING = 'bacc0003-0000-4000-8000-000000000003';

    public const string APPLICATION_KEY = 'ptla_snapshot001';

    public const string READ_ONLY_KEY = 'ptla_snapshot002';

    /** Database passwords that exist before any request; anything else was generated. */
    public const array DATABASE_PASSWORDS = ['Main-Pa$$word/1', 'Logs-Pa$$word\\2', 'Lab-"Pa$$word"'];

    /** A valid ed25519 public key that no world user owns yet. */
    public const string NEW_SSH_KEY = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOaXIq09NH4a93EVdrvHYiZ67Wj+GBEBQ9ou4W0qSYm2 snapshot@example.test';

    /** @var array<string, int> next generated primary key per table */
    private static array $ids = [];

    private static string $timezone = 'UTC';

    /** Undo the process-wide state build() changed. */
    public static function tearDown(): void
    {
        Uuid::setFactory(new UuidFactory);
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        date_default_timezone_set(self::$timezone);
    }

    /**
     * Pin the environment, empty the tables and build the world. Runs inside the test
     * transaction, so everything here is rolled back afterwards.
     */
    public static function build(): void
    {
        self::pinEnvironment();
        self::wipe();
        self::populate();
    }

    /**
     * The Authorization header for an application API key of the root administrator. The
     * world's own key (id 1) already exists; any other id is created with the given
     * permissions, and permissions not given default to read and write.
     *
     * @param  array<string, int>  $permissions
     */
    public static function applicationKey(array $permissions = [], int $id = 1): string
    {
        $identifier = $id === 1 ? self::APPLICATION_KEY : sprintf('ptla_acl%08d', $id);
        $token = sprintf('snapshot-token-%017d', $id);

        if (! ApiKey::query()->whereKey($id)->exists()) {
            self::createApplicationKey($id, $identifier, $token, $permissions);
        }

        return 'Bearer '.$identifier.$token;
    }

    /**
     * Run every request of a scenario against the world and return the snapshot: one
     * section per request holding the request line, then "{status} {content-type}" and
     * the raw response body, byte for byte.
     *
     * @param  array{as?: int, key?: array<string, int>, setup?: Closure, redact?: list<string>, requests: list<array{0: string, 1: string, 2?: array<string, mixed>}>}  $scenario
     */
    public static function run(TestCase $test, array $scenario): string
    {
        if (isset($scenario['as'])) {
            $test->actingAs(User::query()->findOrFail($scenario['as']));
        }

        if (isset($scenario['key'])) {
            $test->withHeader('Authorization', self::applicationKey($scenario['key'], 50));
        }

        if (isset($scenario['setup'])) {
            $scenario['setup']->call($test);
        }

        $sections = [];
        foreach ($scenario['requests'] as $request) {
            [$method, $uri] = $request;
            $body = array_map(fn (mixed $value): mixed => $value instanceof Closure ? $value() : $value, $request[2] ?? []);

            $response = match ($method) {
                'GET' => $test->getJson($uri),
                'UPLOAD' => $test->post($uri, $body, ['Accept' => 'application/json']),
                default => $test->json($method, $uri, $body),
            };

            $sections[] = self::section($method, $uri, $response, $scenario['redact'] ?? []);
        }

        return implode("\n\n", $sections)."\n";
    }

    /**
     * The include matrix for a GET endpoint: the plain response, each include alone, all of
     * them together, a depth-2 include, a depth-3 include (trimmed to two levels), an
     * unknown name, the include[] array form in reverse order, whitespace with duplicates
     * and empty segments, and a name:modifier include.
     *
     * @param  list<string>  $includes
     * @return list<array{0: string, 1: string}>
     */
    public static function includes(string $uri, array $includes = [], ?string $nested = null, ?string $deep = null, ?string $modifier = null): array
    {
        $requests = [['GET', $uri], ['GET', self::query($uri, 'include=does-not-exist')]];
        if ($includes === []) {
            return $requests;
        }

        foreach ($includes as $include) {
            $requests[] = ['GET', self::query($uri, 'include='.$include)];
        }

        if (count($includes) > 1) {
            $requests[] = ['GET', self::query($uri, 'include='.implode(',', $includes))];
        }

        foreach (array_filter([$nested, $deep, $modifier]) as $include) {
            $requests[] = ['GET', self::query($uri, 'include='.$include)];
        }

        $reversed = array_slice(array_reverse($includes), 0, 2);
        $requests[] = ['GET', self::query($uri, implode('&', array_map(fn (string $include): string => 'include[]='.$include, $reversed)))];

        $first = $includes[0];
        $last = $includes[count($includes) - 1];
        $requests[] = ['GET', self::query($uri, 'include=%20'.$first.'%20,,'.$first.','.$last.'%20,%20')];

        return $requests;
    }

    /**
     * The pagination states of a list: an empty page, page one and two of one item per
     * page, and a page past the last one.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function pages(string $uri, ?string $empty = null): array
    {
        return [
            ...($empty === null ? [] : [['GET', $empty]]),
            ['GET', self::query($uri, 'per_page=1')],
            ['GET', self::query($uri, 'per_page=1&page=2')],
            ['GET', self::query($uri, 'per_page=1&page=99')],
        ];
    }

    public static function query(string $uri, string $query): string
    {
        return $uri.(str_contains($uri, '?') ? '&' : '?').$query;
    }

    /** @param array<string, int> $permissions */
    private static function createApplicationKey(int $id, string $identifier, string $token, array $permissions): void
    {
        ApiKey::factory()->create([
            'id' => $id,
            'user_id' => self::ADMIN,
            'key_type' => ApiKey::TYPE_APPLICATION,
            'identifier' => $identifier,
            'token' => Crypt::encrypt($token),
            'allowed_ips' => null,
            'memo' => $id === 1 ? 'Snapshot application key' : 'Snapshot ACL key '.$id,
            'last_used_at' => null,
            'created_at' => self::CREATED,
            'updated_at' => self::UPDATED,
            ...array_fill_keys(array_map(fn (string $resource): string => AdminAcl::COLUMN_IDENTIFIER.$resource, AdminAcl::getResourceList()), AdminAcl::READ | AdminAcl::WRITE),
            ...$permissions,
        ]);
    }

    /** @param list<string> $redact */
    private static function section(string $method, string $uri, TestResponse $response, array $redact): string
    {
        $content = (string) $response->getContent();
        if (in_array('password', $redact, true)) {
            $content = self::redactGeneratedPasswords($content);
        }

        // Schedule::getNextRunDate() and Utilities::getScheduleNextRunDate() ask the cron
        // library for the next run after the system clock, which Carbon's test clock does not
        // reach, so a schedule write returns a value that moves with the wall clock.
        if (in_array('next_run_at', $redact, true)) {
            $content = (string) preg_replace('/"next_run_at":"[^"]*"/', '"next_run_at":"<wall-clock>"', $content);
        }

        return sprintf("%s %s\n%d %s\n%s", $method, $uri, $response->getStatusCode(), (string) $response->headers->get('Content-Type'), $content);
    }

    /**
     * Database passwords are generated with random_int(), which cannot be seeded, so a
     * password the request generated is replaced by a marker. The stored value is decrypted
     * and only its exact JSON encoding is replaced; every other byte stays as returned.
     */
    private static function redactGeneratedPasswords(string $content): string
    {
        foreach (Database::query()->orderBy('id')->get() as $database) {
            $password = Crypt::decrypt($database->password);
            if (! is_string($password) || in_array($password, self::DATABASE_PASSWORDS, true)) {
                continue;
            }

            $encoded = json_encode($password, JSON_THROW_ON_ERROR);
            $content = str_replace(mb_substr($encoded, 1, -1), '<generated-password-'.$database->id.'>', $content);
        }

        return $content;
    }

    private static function pinEnvironment(): void
    {
        self::$ids = [];
        self::$timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        config([
            'app.url' => self::URL,
            'app.timezone' => 'UTC',
            'app.locale' => 'en',
            'app.fallback_locale' => 'en',
            'activity.hide_admin_activity' => false,
            'backups.default' => Backup::ADAPTER_WINGS,
            'cache.default' => 'array',
            'extensions.enabled' => false,
            'hashids.salt' => 'snapshot-salt',
            'hashids.length' => 8,
            'hashids.alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
            'http.rate_limit.client' => 10000,
            'http.rate_limit.application' => 10000,
            'http.rate_limit.admin' => 10000,
            'pterodactyl.features.new_server_identifiers' => false,
            'pterodactyl.service.author' => 'eggs@example.test',
            'pterodactyl.client_features.databases.enabled' => true,
            'pterodactyl.client_features.databases.allow_random' => true,
            'pterodactyl.client_features.allocations.enabled' => true,
            'pterodactyl.client_features.allocations.range_start' => 25560,
            'pterodactyl.client_features.allocations.range_end' => 25580,
            'pterodactyl.client_features.schedules.per_schedule_task_limit' => 10,
            'database.connections.dynamic' => config('database.connections.mysql'),
        ]);

        URL::forceRootUrl(self::URL);
        URL::forceScheme('https');

        App::forgetInstance(HashidsInterface::class);
        Hashids::clearResolvedInstance(HashidsInterface::class);

        $now = CarbonImmutable::parse(self::NOW, 'UTC');
        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);

        fake()->seed(20260304);

        $strings = 0;
        Str::createRandomStringsUsing(function (int $length) use (&$strings): string {
            $strings++;
            $value = '';
            for ($block = 0; mb_strlen($value) < $length; $block++) {
                $value .= hash('sha256', sprintf('snapshot-string-%d-%d', $strings, $block));
            }

            return mb_substr($value, 0, $length);
        });

        $uuids = new UuidFactory;
        $uuids->setRandomGenerator(new class implements RandomGeneratorInterface
        {
            private int $count = 0;

            public function generate(int $length): string
            {
                $this->count++;
                $value = '';
                for ($block = 0; mb_strlen($value) < $length * 2; $block++) {
                    $value .= hash('sha256', sprintf('snapshot-uuid-%d-%d', $this->count, $block));
                }

                return (string) hex2bin(mb_substr($value, 0, $length * 2));
            }
        });
        Uuid::setFactory($uuids);

        // Str::uuid() already reads the factory above, but Str::orderedUuid() (used by
        // HasVersion4Uuids) builds its own time-based generator unless a factory is set.
        Str::createUuidsUsing(fn (): UuidInterface => Uuid::uuid4());

        // Rows the world does not create itself (activity logs, models created by an
        // endpoint) take the next id from a per-table counter instead of the database's
        // auto-increment, which keeps advancing across rolled-back tests.
        Event::listen('eloquent.creating: *', function (string $event, array $payload): void {
            $model = $payload[0] ?? null;
            if (! $model instanceof Model || ! $model->getIncrementing() || $model->getKey() !== null) {
                return;
            }

            $table = $model->getTable();
            self::$ids[$table] = (self::$ids[$table] ?? 1000) + 1;
            $model->setAttribute($model->getKeyName(), self::$ids[$table]);
        });

        App::instance(DatabaseHostGateway::class, new FakeDatabaseHostGateway);
        App::instance(DynamicDatabaseConnection::class, new FakeDynamicDatabaseConnection);

        Http::fake(fn (HttpRequest $request) => self::wings($request));
    }

    /**
     * Stand in for Wings and the egg catalog. Every daemon call succeeds; the calls whose
     * bodies an endpoint returns get fixed payloads.
     */
    private static function wings(HttpRequest $request): mixed
    {
        $path = (string) parse_url($request->url(), PHP_URL_PATH);

        if ($request->url() === EggCatalogService::INDEX_URL) {
            return Http::response([[
                'id' => 'games-paper',
                'name' => 'Paper',
                'description' => 'A Minecraft server',
                'category' => 'games',
                'downloadUrl' => 'https://raw.githubusercontent.com/pterodactyl/game-eggs/main/paper.json',
            ]]);
        }

        if (str_starts_with($request->url(), 'https://raw.githubusercontent.com/')) {
            return Http::response(App::make(ExportsEggs::class)->export(Egg::query()->findOrFail(1)));
        }

        if (str_ends_with($path, '/files/list-directory')) {
            return Http::response([
                ['name' => 'plugins', 'mode' => 'drwxr-xr-x', 'mode_bits' => '755', 'size' => 4096, 'file' => false, 'symlink' => false, 'mime' => 'inode/directory', 'created' => '2026-01-02T03:04:05+00:00', 'modified' => '2026-02-03T04:05:06+00:00'],
                ['name' => 'server.properties', 'mode' => '-rw-r--r--', 'mode_bits' => '644', 'size' => 1187, 'file' => true, 'symlink' => false, 'mime' => 'text/plain; charset=utf-8', 'created' => '2026-01-02T03:04:05+00:00', 'modified' => '2026-02-03T04:05:06+00:00'],
                ['name' => 'latest.log', 'mode' => 'Lrwxrwxrwx', 'mode_bits' => '777', 'size' => 0, 'file' => true, 'symlink' => true, 'created' => '2026-01-02T03:04:05Z', 'modified' => '2026-02-03T04:05:06Z'],
            ]);
        }

        if (str_ends_with($path, '/files/compress')) {
            return Http::response(['name' => 'archive-2026-03-04.tar.gz', 'mode' => '-rw-r--r--', 'mode_bits' => '644', 'size' => 524288, 'file' => true, 'symlink' => false, 'mime' => 'application/tar+gzip', 'created' => '2026-03-04T05:06:07+00:00', 'modified' => '2026-03-04T05:06:07+00:00']);
        }

        if ($request->method() === 'GET' && preg_match('#^/api/servers/[^/]+$#', $path) === 1) {
            return Http::response(['state' => 'running', 'is_suspended' => false, 'utilization' => ['memory_bytes' => 1073741824, 'cpu_absolute' => 12.5, 'disk_bytes' => 2147483648, 'network' => ['rx_bytes' => 1024, 'tx_bytes' => 2048], 'uptime' => 3600000]]);
        }

        return Http::response('', 204);
    }

    private static function wipe(): void
    {
        $tables = array_map(
            fn (object $row): string => (string) ($row->name ?? throw new UnexpectedValueException('Expected a table name.')),
            DB::select("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' AND table_name <> 'migrations' ORDER BY table_name"),
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                DB::table($table)->delete();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private static function populate(): void
    {
        $stamps = ['created_at' => self::CREATED, 'updated_at' => self::UPDATED];

        self::users($stamps);
        self::infrastructure($stamps);
        self::eggs($stamps);
        self::servers($stamps);
        self::serverResources($stamps);
        self::accountResources($stamps);
        self::activity();
    }

    /** @param array{created_at: string, updated_at: string} $stamps */
    private static function users(array $stamps): void
    {
        $users = [
            [self::ADMIN, self::ADMIN_UUID, null, 'admin', 'admin@example.test', 'Ada', 'Admin', true, false],
            [self::OWNER, self::OWNER_UUID, 'ext-owner', 'owner', 'owner@example.test', 'Olive', 'Owner', false, false],
            [self::HELPER, self::HELPER_UUID, null, 'helper', 'helper@example.test', 'Hal', 'Helper', false, false],
            [self::LIMITED, self::LIMITED_UUID, null, 'limited', 'limited@example.test', 'Lim', 'Ited', false, false],
            [self::SPARE, self::SPARE_UUID, 'ext-spare', 'spare', 'Spare.User@Example.test', 'Spare', 'User', false, true],
        ];

        foreach ($users as [$id, $uuid, $external, $username, $email, $first, $last, $admin, $totp]) {
            User::factory()->create([
                'id' => $id,
                'uuid' => $uuid,
                'external_id' => $external,
                'username' => $username,
                'email' => $email,
                'name_first' => $first,
                'name_last' => $last,
                'language' => 'en',
                'root_admin' => $admin,
                'use_totp' => $totp,
                'remember_token' => null,
                ...$stamps,
            ]);
        }
    }

    /** @param array{created_at: string, updated_at: string} $stamps */
    private static function infrastructure(array $stamps): void
    {
        Location::factory()->create(['id' => 1, 'short' => 'us-east', 'long' => 'US East / Virginia', ...$stamps]);
        Location::factory()->create(['id' => 2, 'short' => 'eu-west', 'long' => 'EU West', ...$stamps]);
        Location::factory()->create(['id' => 3, 'short' => 'ap-empty', 'long' => null, ...$stamps]);

        Node::factory()->create([
            'id' => 1, 'uuid' => 'd0de0001-0000-4000-8000-000000000001', 'public' => true, 'name' => 'node-alpha',
            'description' => 'Primary "alpha" node', 'location_id' => 1, 'fqdn' => 'alpha.example.test', 'scheme' => 'https',
            'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 8192, 'memory_overallocate' => 0, 'disk' => 102400,
            'disk_overallocate' => -1, 'upload_size' => 100, 'daemon_token_id' => 'alphatokenid0001',
            'daemon_token' => Crypt::encrypt(str_repeat('a', Node::DAEMON_TOKEN_LENGTH)), 'daemonListen' => 8080, 'daemonSFTP' => 2022,
            'daemonBase' => '/var/lib/pterodactyl/volumes', ...$stamps,
        ]);
        Node::factory()->create([
            'id' => 2, 'uuid' => 'd0de0002-0000-4000-8000-000000000002', 'public' => false, 'name' => 'node-beta',
            'description' => null, 'location_id' => 2, 'fqdn' => 'beta.example.test', 'scheme' => 'http',
            'behind_proxy' => true, 'maintenance_mode' => true, 'memory' => 4096, 'memory_overallocate' => 10, 'disk' => 51200,
            'disk_overallocate' => 0, 'upload_size' => 256, 'daemon_token_id' => 'betatokenid00002',
            'daemon_token' => Crypt::encrypt(str_repeat('b', Node::DAEMON_TOKEN_LENGTH)), 'daemonListen' => 8443, 'daemonSFTP' => 2222,
            'daemonBase' => '/srv/daemon-data', ...$stamps,
        ]);

        $allocations = [
            [1, 1, '192.0.2.10', 'play.example.test', 25565, 'Primary <game> port'],
            [2, 1, '192.0.2.10', null, 25566, 'Query/RCON'],
            [3, 1, '192.0.2.10', null, 25567, null],
            [4, 1, '192.0.2.10', null, 25568, null],
            [5, 1, '192.0.2.11', null, 25565, null],
            [6, 1, '192.0.2.11', 'spare.example.test', 25566, null],
            [7, 2, '198.51.100.20', null, 30000, null],
            [8, 2, '198.51.100.20', null, 30001, null],
        ];

        foreach ($allocations as [$id, $node, $ip, $alias, $port, $notes]) {
            Allocation::factory()->create(['id' => $id, 'node_id' => $node, 'ip' => $ip, 'ip_alias' => $alias, 'port' => $port, 'server_id' => null, 'notes' => $notes, ...$stamps]);
        }

        DatabaseHost::factory()->create(['id' => 1, 'name' => 'primary-db', 'host' => '10.0.0.5', 'port' => 3306, 'username' => 'pterodactyl', 'max_databases' => 10, 'node_id' => 1, ...$stamps]);
        DatabaseHost::factory()->create(['id' => 2, 'name' => 'empty-host', 'host' => 'db2.example.test', 'port' => 3307, 'username' => 'admin', 'max_databases' => null, 'node_id' => null, ...$stamps]);

        Tag::factory()->create(['id' => 1, 'name' => 'stored minecraft name', 'slug' => 'minecraft', 'color' => null, 'legacy_nest_id' => 1, ...$stamps]);
        Tag::factory()->create(['id' => 2, 'name' => 'Premium', 'slug' => 'premium', 'color' => '#ffaa00', 'legacy_nest_id' => null, ...$stamps]);
        Tag::factory()->create(['id' => 3, 'name' => 'Reserved', 'slug' => 'reserved', 'color' => null, 'legacy_nest_id' => null, ...$stamps]);
        Tag::factory()->create(['id' => 4, 'name' => 'Unused', 'slug' => 'unused', 'color' => '#000000', 'legacy_nest_id' => 7, ...$stamps]);

        Mount::factory()->create(['id' => 1, 'uuid' => 'a0a00001-0000-4000-8000-000000000001', 'name' => 'shared-mods', 'description' => 'Shared mod directory', 'source' => '/mnt/mods', 'target' => '/home/container/mods', 'read_only' => true, 'user_mountable' => false]);
        Mount::factory()->create(['id' => 2, 'uuid' => 'a0a00002-0000-4000-8000-000000000002', 'name' => 'scratch', 'description' => null, 'source' => '/mnt/scratch', 'target' => '/scratch', 'read_only' => false, 'user_mountable' => true]);
    }

    /** @param array{created_at: string, updated_at: string} $stamps */
    private static function eggs(array $stamps): void
    {
        Egg::factory()->create([
            'id' => 1,
            'uuid' => 'e9900001-0000-4000-8000-000000000001',
            'author' => 'support@example.test',
            'name' => 'Paper',
            'description' => 'High performance Minecraft server.',
            'features' => ['eula', 'java_version'],
            'docker_images' => ['Java 21' => 'ghcr.io/pterodactyl/yolks:java_21', 'Java 17' => 'ghcr.io/pterodactyl/yolks:java_17'],
            'file_denylist' => [],
            'update_url' => null,
            'config_files' => '{"server.properties":{"parser":"properties","find":{"server-ip":"0.0.0.0","server-port":"{{server.build.default.port}}"}}}',
            'config_startup' => '{"done":")! For help, type "}',
            'config_logs' => '{}',
            'config_stop' => 'stop',
            'config_from' => null,
            'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}',
            'script_container' => 'ghcr.io/pterodactyl/installers:alpine',
            'copy_script_from' => null,
            'script_entry' => 'ash',
            'script_is_privileged' => true,
            'script_install' => "#!/bin/ash\necho \"installing\"",
            'force_outgoing_ip' => false,
            ...$stamps,
        ]);
        Egg::factory()->create([
            'id' => 2,
            'uuid' => 'e9900002-0000-4000-8000-000000000002',
            'author' => 'support@example.test',
            'name' => 'Paper Child',
            'description' => null,
            'features' => null,
            'docker_images' => ['Java 21' => 'ghcr.io/pterodactyl/yolks:java_21'],
            'file_denylist' => ['*.secret', 'config/private.yml'],
            'update_url' => null,
            'config_files' => null,
            'config_startup' => null,
            'config_logs' => null,
            'config_stop' => null,
            'config_from' => 1,
            'startup' => 'java -jar {{SERVER_JARFILE}}',
            'script_container' => 'alpine:3.4',
            'copy_script_from' => 1,
            'script_entry' => 'ash',
            'script_is_privileged' => false,
            'script_install' => null,
            'force_outgoing_ip' => true,
            ...$stamps,
        ]);
        Egg::factory()->create([
            'id' => 3,
            'uuid' => 'e9900003-0000-4000-8000-000000000003',
            'author' => 'empty@example.test',
            'name' => 'Empty Egg',
            'description' => '',
            'features' => [],
            'docker_images' => [],
            'file_denylist' => null,
            'update_url' => null,
            'config_files' => '[]',
            'config_startup' => '{}',
            'config_logs' => '[]',
            'config_stop' => '^C',
            'config_from' => null,
            'startup' => './start.sh',
            'script_container' => 'alpine:3.4',
            'copy_script_from' => null,
            'script_entry' => 'sh',
            'script_is_privileged' => true,
            'script_install' => '',
            'force_outgoing_ip' => false,
            ...$stamps,
        ]);

        $variables = [
            [1, 1, 'Server Jar File', 'The name of the server jarfile to run.', 'SERVER_JARFILE', 'server.jar', true, true, 'required|regex:/^([\w\d._-]+)(\.jar)$/', 1],
            [2, 1, 'Build Number', 'The build to download, or "latest".', 'BUILD_NUMBER', 'latest', true, false, 'required|string|max:20', 2],
            [3, 1, 'Hidden Token', 'Never shown to clients.', 'HIDDEN_TOKEN', 'secret', false, false, 'nullable|string', null],
            [4, 2, 'Server Jar File', 'The jar the child egg runs.', 'SERVER_JARFILE', 'child.jar', true, true, 'required|string', 1],
        ];

        foreach ($variables as [$id, $egg, $name, $description, $env, $default, $viewable, $editable, $rules, $sort]) {
            EggVariable::factory()->create([
                'id' => $id, 'egg_id' => $egg, 'name' => $name, 'description' => $description, 'env_variable' => $env,
                'default_value' => $default, 'user_viewable' => $viewable, 'user_editable' => $editable, 'rules' => $rules,
                'sort_order' => $sort, ...$stamps,
            ]);
        }

        DB::table('taggables')->insert([
            ['tag_id' => 1, 'kind' => 'egg', 'taggable_type' => 'egg', 'taggable_id' => 1],
            ['tag_id' => 2, 'kind' => 'egg', 'taggable_type' => 'egg', 'taggable_id' => 1],
            ['tag_id' => 1, 'kind' => 'egg', 'taggable_type' => 'egg', 'taggable_id' => 2],
            ['tag_id' => 1, 'kind' => 'egg', 'taggable_type' => 'node', 'taggable_id' => 1],
            ['tag_id' => 3, 'kind' => 'deployment', 'taggable_type' => 'node', 'taggable_id' => 2],
        ]);
        DB::table('egg_mount')->insert(['egg_id' => 1, 'mount_id' => 1]);
        DB::table('mount_node')->insert(['node_id' => 1, 'mount_id' => 1]);
    }

    /** @param array{created_at: string, updated_at: string} $stamps */
    private static function servers(array $stamps): void
    {
        $servers = [
            [
                'id' => 1, 'external_id' => 'ext-survival', 'uuid' => self::SURVIVAL, 'uuidShort' => '5e7f0001', 'node_id' => 1,
                'name' => 'Survival', 'description' => 'Main survival world', 'status' => null, 'skip_scripts' => false,
                'owner_id' => self::OWNER, 'memory' => 2048, 'swap' => 512, 'disk' => 10240, 'io' => 500, 'cpu' => 200,
                'threads' => '0-3', 'oom_disabled' => true, 'allocation_id' => 1, 'egg_id' => 1,
                'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}', 'image' => 'ghcr.io/pterodactyl/yolks:java_21',
                'allocation_limit' => 5, 'database_limit' => 5, 'backup_limit' => 5, 'installed_at' => self::CREATED,
            ],
            [
                'id' => 2, 'external_id' => null, 'uuid' => self::CREATIVE, 'uuidShort' => '5e7f0002', 'node_id' => 1,
                'name' => 'Creative', 'description' => '', 'status' => Server::STATUS_INSTALLING, 'skip_scripts' => true,
                'owner_id' => self::OWNER, 'memory' => 1024, 'swap' => 0, 'disk' => 5120, 'io' => 500, 'cpu' => 0,
                'threads' => null, 'oom_disabled' => false, 'allocation_id' => 3, 'egg_id' => 2,
                'startup' => 'java -jar {{SERVER_JARFILE}}', 'image' => 'ghcr.io/pterodactyl/yolks:java_17',
                'allocation_limit' => null, 'database_limit' => 0, 'backup_limit' => 0, 'installed_at' => null,
            ],
            [
                'id' => 3, 'external_id' => 'ext-lab', 'uuid' => self::LAB, 'uuidShort' => '5e7f0003', 'node_id' => 2,
                'name' => 'Admin Lab', 'description' => 'Testing ground', 'status' => null, 'skip_scripts' => false,
                'owner_id' => self::ADMIN, 'memory' => 512, 'swap' => -1, 'disk' => 1024, 'io' => 10, 'cpu' => 50,
                'threads' => null, 'oom_disabled' => true, 'allocation_id' => 8, 'egg_id' => 1,
                'startup' => 'java -jar {{SERVER_JARFILE}}', 'image' => 'ghcr.io/pterodactyl/yolks:java_21',
                'allocation_limit' => 1, 'database_limit' => 1, 'backup_limit' => 1, 'installed_at' => self::CREATED,
            ],
        ];

        foreach ($servers as $server) {
            Server::factory()->create([...$server, ...$stamps]);
        }

        Allocation::query()->whereKey([1, 2])->update(['server_id' => 1]);
        Allocation::query()->whereKey(3)->update(['server_id' => 2]);
        Allocation::query()->whereKey(8)->update(['server_id' => 3]);

        DB::table('server_variables')->insert([
            ['id' => 1, 'server_id' => 1, 'variable_id' => 1, 'variable_value' => 'paper.jar', ...$stamps],
            ['id' => 2, 'server_id' => 1, 'variable_id' => 3, 'variable_value' => 'hidden-value', ...$stamps],
            ['id' => 3, 'server_id' => 2, 'variable_id' => 4, 'variable_value' => 'child.jar', ...$stamps],
        ]);

        Subuser::factory()->create(['id' => 1, 'user_id' => self::HELPER, 'server_id' => 1, 'permissions' => [
            'websocket.connect', 'control.console', 'control.start', 'allocation.read', 'startup.read', 'user.read',
            'database.read', 'database.view_password', 'backup.read', 'schedule.read', 'activity.read', 'file.read',
        ], ...$stamps]);
        Subuser::factory()->create(['id' => 2, 'user_id' => self::LIMITED, 'server_id' => 1, 'permissions' => [
            'websocket.connect', 'control.console', 'database.read', 'database.create', 'database.update', 'backup.read',
            'schedule.read', 'activity.read', 'file.read',
        ], ...$stamps]);

        DB::table('mount_server')->insert(['server_id' => 1, 'mount_id' => 1]);

        ServerTransfer::factory()->create([
            'id' => 1, 'server_id' => 2, 'successful' => null, 'old_node' => 1, 'new_node' => 2, 'old_allocation' => 3,
            'new_allocation' => 7, 'old_additional_allocations' => [], 'new_additional_allocations' => [], 'archived' => false, ...$stamps,
        ]);
    }

    /** @param array{created_at: string, updated_at: string} $stamps */
    private static function serverResources(array $stamps): void
    {
        $databases = [
            [1, 1, 's1_main', 'u1_mainuser', '%', self::DATABASE_PASSWORDS[0], 10],
            [2, 1, 's1_logs', 'u1_logsuser', '10.0.0.%', self::DATABASE_PASSWORDS[1], 0],
            [3, 3, 's3_lab', 'u3_labuser', '%', self::DATABASE_PASSWORDS[2], null],
        ];

        foreach ($databases as [$id, $server, $name, $username, $remote, $password, $connections]) {
            Database::factory()->create([
                'id' => $id, 'server_id' => $server, 'database_host_id' => 1, 'database' => $name, 'username' => $username,
                'remote' => $remote, 'password' => Crypt::encrypt($password), 'max_connections' => $connections, ...$stamps,
            ]);
        }

        Backup::factory()->create([
            'id' => 1, 'server_id' => 1, 'uuid' => self::BACKUP_NIGHTLY, 'upload_id' => null, 'is_successful' => true, 'is_locked' => true,
            'name' => 'Nightly', 'ignored_files' => ['*.log', 'cache/'], 'disk' => Backup::ADAPTER_WINGS, 'checksum' => 'sha1:0123456789abcdef',
            'bytes' => 1048576, 'completed_at' => '2026-01-02 04:04:05', ...$stamps,
        ]);
        Backup::factory()->create([
            'id' => 2, 'server_id' => 1, 'uuid' => self::BACKUP_FAILED, 'upload_id' => null, 'is_successful' => false, 'is_locked' => false,
            'name' => 'Failed attempt', 'ignored_files' => [], 'disk' => Backup::ADAPTER_WINGS, 'checksum' => null,
            'bytes' => 0, 'completed_at' => '2026-01-02 03:14:05', ...$stamps,
        ]);
        Backup::factory()->create([
            'id' => 3, 'server_id' => 1, 'uuid' => self::BACKUP_RUNNING, 'upload_id' => null, 'is_successful' => false, 'is_locked' => false,
            'name' => 'In progress', 'ignored_files' => ['world/'], 'disk' => Backup::ADAPTER_WINGS, 'checksum' => null,
            'bytes' => 0, 'completed_at' => null, ...$stamps,
        ]);

        Schedule::factory()->create([
            'id' => 1, 'server_id' => 1, 'name' => 'Nightly restart', 'cron_day_of_week' => '*', 'cron_month' => '*',
            'cron_day_of_month' => '*', 'cron_hour' => '3', 'cron_minute' => '0', 'is_active' => true, 'is_processing' => false,
            'only_when_online' => true, 'last_run_at' => self::CREATED, 'next_run_at' => '2026-03-05 03:00:00', ...$stamps,
        ]);
        Schedule::factory()->create([
            'id' => 2, 'server_id' => 1, 'name' => 'Disabled cleanup', 'cron_day_of_week' => '1-5', 'cron_month' => '*',
            'cron_day_of_month' => '*', 'cron_hour' => '*', 'cron_minute' => '*/15', 'is_active' => false, 'is_processing' => false,
            'only_when_online' => false, 'last_run_at' => null, 'next_run_at' => null, ...$stamps,
        ]);

        Task::factory()->create(['id' => 1, 'schedule_id' => 1, 'sequence_id' => 1, 'action' => 'command', 'payload' => 'say "Restarting" in 60s', 'time_offset' => 0, 'is_queued' => false, 'continue_on_failure' => true, ...$stamps]);
        Task::factory()->create(['id' => 2, 'schedule_id' => 1, 'sequence_id' => 2, 'action' => 'power', 'payload' => 'restart', 'time_offset' => 60, 'is_queued' => true, 'continue_on_failure' => false, ...$stamps]);
    }

    /** @param array{created_at: string, updated_at: string} $stamps */
    private static function accountResources(array $stamps): void
    {
        self::applicationKey();

        ApiKey::factory()->create([
            'id' => 2, 'user_id' => self::ADMIN, 'key_type' => ApiKey::TYPE_APPLICATION, 'identifier' => self::READ_ONLY_KEY,
            'token' => Crypt::encrypt(sprintf('snapshot-token-%017d', 2)), 'allowed_ips' => null, 'memo' => 'Read only key',
            'last_used_at' => '2026-02-03 06:05:06', 'r_servers' => AdminAcl::READ, 'r_nodes' => 0, 'r_allocations' => 0,
            'r_users' => AdminAcl::READ, 'r_locations' => 0, 'r_eggs' => 0, 'r_database_hosts' => 0, 'r_server_databases' => 0, ...$stamps,
        ]);
        ApiKey::factory()->create([
            'id' => 3, 'user_id' => self::OWNER, 'key_type' => ApiKey::TYPE_ACCOUNT, 'identifier' => 'ptlc_snapshot003',
            'token' => Crypt::encrypt(sprintf('snapshot-token-%017d', 3)), 'allowed_ips' => ['127.0.0.1', '10.0.0.0/8'],
            'memo' => 'CLI access', 'last_used_at' => '2026-02-03 07:05:06', ...$stamps,
        ]);
        ApiKey::factory()->create([
            'id' => 4, 'user_id' => self::OWNER, 'key_type' => ApiKey::TYPE_ACCOUNT, 'identifier' => 'ptlc_snapshot004',
            'token' => Crypt::encrypt(sprintf('snapshot-token-%017d', 4)), 'allowed_ips' => [], 'memo' => 'Second key',
            'last_used_at' => null, ...$stamps,
        ]);

        UserSSHKey::factory()->create([
            'id' => 1, 'user_id' => self::OWNER, 'name' => 'Laptop',
            'fingerprint' => 'SHA256:snapshotFingerprintLaptop0000000000000000000',
            'public_key' => "-----BEGIN PUBLIC KEY-----\nMCowBQYDK2VwAyEAsnapshotPublicKeyLaptop00000000000000000=\n-----END PUBLIC KEY-----",
            ...$stamps,
        ]);
    }

    private static function activity(): void
    {
        $logs = [
            [1, 'admin:server.build', self::ADMIN, null, '203.0.113.5', ['name' => 'Survival'], [['server', 1]]],
            [2, 'admin:user.create', self::ADMIN, null, '203.0.113.5', ['email' => 'owner@example.test'], [['user', self::OWNER]]],
            [3, 'admin:node.update', self::ADMIN, 1, '203.0.113.6', ['name' => 'node-alpha', 'files' => ['config.yml', 'wings.yml']], [['node', 1]]],
            [4, 'user:account.email-changed', self::OWNER, null, '198.51.100.7', ['old' => 'old@example.test', 'new' => 'owner@example.test'], [['user', self::OWNER]]],
            [5, 'server:power.start', self::HELPER, null, '198.51.100.8', [], [['server', 1]]],
            [6, 'server:file.delete', self::OWNER, null, '198.51.100.7', ['directory' => 'plugins//old', 'files' => ['old.jar', 'older.jar']], [['server', 1]]],
            [7, 'auth:fail', null, null, '192.0.2.99', ['username' => 'owner', 'ip' => '192.0.2.99'], [['user', self::OWNER]]],
            [8, 'server:file.upload', self::OWNER, null, '198.51.100.7', ['files' => ['a.txt']], [['server', 1]]],
            [9, 'server:backup.start', self::ADMIN, null, '203.0.113.5', ['name' => 'Nightly', 'locked' => true], [['server', 1], ['backup', 1]]],
            [10, 'server:console.command', self::LIMITED, null, '198.51.100.9', ['command' => 'say <hi> & "bye"'], [['server', 1]]],
        ];

        $subject = 0;
        foreach ($logs as [$id, $event, $actor, $key, $ip, $properties, $subjects]) {
            $log = ActivityLog::factory()->make([
                'batch' => $id === 9 ? 'ba7c0009-0000-4000-8000-000000000009' : null,
                'event' => $event,
                'ip' => $ip,
                'description' => $id === 3 ? 'Updated the node configuration.' : null,
                'actor_type' => $actor === null ? null : 'user',
                'actor_id' => $actor,
                'api_key_id' => $key,
                'properties' => $properties,
            ]);
            $log->forceFill(['id' => $id, 'timestamp' => CarbonImmutable::parse(self::CREATED)->addMinutes($id)])->save();

            foreach ($subjects as [$type, $subjectId]) {
                DB::table('activity_log_subjects')->insert(['id' => ++$subject, 'activity_log_id' => $id, 'subject_type' => $type, 'subject_id' => $subjectId]);
            }
        }
    }
}
