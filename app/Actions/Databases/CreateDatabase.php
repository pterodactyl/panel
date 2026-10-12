<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Databases\CreatesDatabases;
use Pterodactyl\Exceptions\Repository\DuplicateDatabaseNameException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Throwable;

final class CreateDatabase implements CreatesDatabases
{
    /**
     * Rules every database's remote connection pattern must pass: the hosts its user may
     * connect from, such as `%` or `192.168.1.%`.
     *
     * @var list<string>
     */
    public const array REMOTE = ['required', 'string', 'regex:/^[\w\-\/.%:]+$/'];

    /** @see \Pterodactyl\Support\DatabaseName::generateUnique() */
    private const string MATCH_NAME_REGEX = '/^(s[\d]+_)(.*)$/';

    private bool $validateDatabaseLimit = true;

    public function __construct(
        private readonly DynamicDatabaseConnection $dynamic,
        private readonly DatabaseHostGateway $gateway,
    ) {}

    /**
     * The name is the full one, already prefixed with "s{server_id}_" by
     * DatabaseName::generateUnique().
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'database' => ['required', 'string', 'alpha_dash', 'between:3,48', 'regex:'.self::MATCH_NAME_REGEX],
            'remote' => self::REMOTE,
            'database_host_id' => ['required', 'integer', 'exists:database_hosts,id'],
            'max_connections' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function setValidateDatabaseLimit(bool $validate): CreatesDatabases
    {
        $this->validateDatabaseLimit = $validate;

        return $this;
    }

    public function create(Server $server, array $input): Database
    {
        throw_unless(config('pterodactyl.client_features.databases.enabled'), DatabaseClientFeatureNotEnabledException::class);

        if ($this->validateDatabaseLimit) {
            throw_if(($server->database_limit) !== null && $server->databases()->count() >= $server->database_limit, TooManyDatabasesException::class);
        }

        $validated = Validator::make($input, self::rules())->safe();
        $hostId = $validated->integer('database_host_id');

        $username = sprintf('u%d_%s', $server->id, Str::random(10));
        $password = Utilities::randomStringWithSpecialCharacters(24);
        $data = [
            'server_id' => $server->id,
            'database_host_id' => $hostId,
            'database' => $validated['database'],
            'username' => $username,
            'remote' => $validated['remote'],
            'password' => Crypt::encrypt($password),
        ];

        // A database given no connection limit takes the column default; a blank one is null.
        if ($validated->has('max_connections')) {
            $data['max_connections'] = $validated->filled('max_connections') ? $validated['max_connections'] : null;
        }

        $database = null;

        try {
            $database = DB::transaction(function () use ($data, $hostId, $password, &$database): Database {
                $this->dynamic->set('dynamic', $hostId);
                $database = $this->createModel($data);
                $this->gateway->createDatabase($database->database);
                $this->gateway->createUser(
                    $database->username,
                    $database->remote,
                    $password,
                    $database->max_connections
                );
                $this->gateway->assignUserToDatabase($database->database, $database->username, $database->remote);
                $this->gateway->flush();

                return $database;
            });
        } catch (Throwable $throwable) {
            // The model row may have been created before provisioning failed, so clean up any host state that exists.
            if ($database instanceof Database) {
                rescue(fn (): bool => $this->gateway->dropDatabase($database->database));
                rescue(fn (): bool => $this->gateway->dropUser($database->username, $database->remote));
                rescue(fn (): bool => $this->gateway->flush());
            }

            throw $throwable;
        }

        Activity::event('server:database.create')->subject($server, $database)->property('name', $database->database)->log();

        return $database;
    }

    /** @param array<string, mixed> $data */
    private function createModel(array $data): Database
    {
        $exists = Database::query()->where('server_id', $data['server_id'])
            ->where('database', $data['database'])
            ->exists();

        throw_if($exists, DuplicateDatabaseNameException::class, 'A database with that name already exists for this server.');

        $database = (new Database)->forceFill($data);
        $database->saveOrFail();

        return $database;
    }
}
