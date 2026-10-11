<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Arr;
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
     * A database's fields. The name is the full one, already prefixed with "s{server_id}_" by
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

    /**
     * Validates database input and returns the model attributes it describes. The connection
     * limit is left out when it is not given, so the database takes the column default; a
     * blank one is stored as null. Null and 0 both leave the user's connections unlimited.
     *
     * @param  array<string, mixed>  $input
     * @return DatabaseCreationData
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        $attributes = [
            'database' => $input->string('database')->toString(),
            'remote' => $input->string('remote')->toString(),
            'database_host_id' => $input->integer('database_host_id'),
        ];

        if ($input->has('max_connections')) {
            $attributes['max_connections'] = $input->filled('max_connections') ? $input->integer('max_connections') : null;
        }

        return $attributes;
    }

    public function setValidateDatabaseLimit(bool $validate): CreatesDatabases
    {
        $this->validateDatabaseLimit = $validate;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     */
    public function create(Server $server, array $input): Database
    {
        throw_unless(config('pterodactyl.client_features.databases.enabled'), DatabaseClientFeatureNotEnabledException::class);

        if ($this->validateDatabaseLimit) {
            throw_if(($server->database_limit) !== null && $server->databases()->count() >= $server->database_limit, TooManyDatabasesException::class);
        }

        $data = [
            ...self::attributes($input),
            'server_id' => $server->id,
            'username' => sprintf('u%d_%s', $server->id, Str::random(10)),
            'password' => Crypt::encrypt(Utilities::randomStringWithSpecialCharacters(24)),
        ];

        $database = null;

        try {
            $database = DB::transaction(function () use ($data, &$database): Database {
                $this->dynamic->set('dynamic', $data['database_host_id']);
                $database = $this->createModel($data);
                $this->gateway->createDatabase($database->database);
                $this->gateway->createUser(
                    $database->username,
                    $database->remote,
                    Arr::string(['password' => Crypt::decrypt($database->password)], 'password'),
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

    /** @param DatabaseModelCreationData $data */
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
