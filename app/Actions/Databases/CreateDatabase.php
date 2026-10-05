<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Pterodactyl\Contracts\Databases\CreatesDatabases;
use Pterodactyl\Exceptions\Repository\DuplicateDatabaseNameException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

final class CreateDatabase implements CreatesDatabases
{
    /** @see \Pterodactyl\Support\DatabaseName::generateUnique() */
    private const string MATCH_NAME_REGEX = '/^(s[\d]+_)(.*)$/';

    private bool $validateDatabaseLimit = true;

    public function __construct(
        private readonly DynamicDatabaseConnection $dynamic,
        private readonly DatabaseHostGateway $gateway,
    ) {}

    public function setValidateDatabaseLimit(bool $validate): CreatesDatabases
    {
        $this->validateDatabaseLimit = $validate;

        return $this;
    }

    /** @param DatabaseCreationData $data
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     */
    public function create(Server $server, array $data): Database
    {
        throw_unless(config('pterodactyl.client_features.databases.enabled'), DatabaseClientFeatureNotEnabledException::class);

        if ($this->validateDatabaseLimit) {
            throw_if(($server->database_limit) !== null && $server->databases()->count() >= $server->database_limit, TooManyDatabasesException::class);
        }

        throw_if(empty($data['database']) || ! preg_match(self::MATCH_NAME_REGEX, $data['database']), InvalidArgumentException::class, 'The database name passed to CreateDatabase::create MUST be prefixed with "s{server_id}_".');

        $data = [
            ...$data,
            'server_id' => $server->id,
            'username' => sprintf('u%d_%s', $server->id, Str::random(10)),
            'password' => Crypt::encrypt(Utilities::randomStringWithSpecialCharacters(24)),
        ];

        $database = null;

        try {
            return DB::transaction(function () use ($data, &$database): Database {
                $this->dynamic->set('dynamic', $data['database_host_id']);
                $database = $this->createModel($data);
                $this->gateway->createDatabase($database->database);
                $this->gateway->createUser(
                    $database->username,
                    $database->remote,
                    JsonValueGuard::string(Crypt::decrypt($database->password)),
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
