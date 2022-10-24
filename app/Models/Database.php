<?php

namespace Pterodactyl\Models;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

/**
 * @property int $id
 * @property int $server_id
 * @property int $database_host_id
 * @property string $database
 * @property string $username
 * @property string $remote
 * @property string $password
 * @property int $max_connections
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\DatabaseHost $host
 */
class Database extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_database';

    public const DEFAULT_CONNECTION_NAME = 'dynamic';

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = ['password'];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'server_id', 'database_host_id', 'database', 'username', 'password', 'remote', 'max_connections',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'server_id' => 'integer',
        'database_host_id' => 'integer',
        'max_connections' => 'integer',
    ];

    public static array $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id',
        'database_host_id' => 'required|exists:database_hosts,id',
        'database' => 'required|string|alpha_dash|between:3,48',
        'username' => 'string|alpha_dash|between:3,100',
        'max_connections' => 'nullable|integer',
        'remote' => 'required|string|regex:/^[\w\-\/.%:]+$/',
        'password' => 'string',
    ];

    /**
     * {@inheritDoc}
     */
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Resolves the database using the ID by checking if the value provided is a HashID
     * string value, or just the ID to the database itself.
     *
     * @param mixed $value
     * @param string|null $field
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        if (is_scalar($value) && ($field ?? $this->getRouteKeyName()) === 'id') {
            $value = ctype_digit((string) $value)
                ? $value
                : Container::getInstance()->make(HashidsInterface::class)->decodeFirst($value);
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }

    /**
     * Gets the host database server associated with a database.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(DatabaseHost::class, 'database_host_id');
    }

    /**
     * Gets the server associated with a database.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Run the provided statement against the database on a given connection.
     */
    private function run(string $statement): bool
    {
        return DB::connection($this->connection)->statement($statement);
    }

    /**
     * Create a new database on a given connection.
     */
    public function createDatabase(string $database): bool
    {
        return $this->run(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database));
    }

    /**
     * Create a new database user on a given connection.
     */
    public function createUser(string $username, string $remote, string $password, ?int $max_connections): bool
    {
        $args = [$username, $remote, $password];
        $command = 'CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\'';

        if (!empty($max_connections)) {
            $args[] = $max_connections;
            $command .= ' WITH MAX_USER_CONNECTIONS %s';
        }

        return $this->run(sprintf($command, ...$args));
    }

    /**
     * Give a specific user access to a given database.
     */
    public function assignUserToDatabase(string $database, string $username, string $remote): bool
    {
        return $this->run(sprintf(
            'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, REFERENCES, INDEX, LOCK TABLES, CREATE ROUTINE, ALTER ROUTINE, EXECUTE, CREATE TEMPORARY TABLES, CREATE VIEW, SHOW VIEW, EVENT, TRIGGER ON `%s`.* TO `%s`@`%s`',
            $database,
            $username,
            $remote
        ));
    }

    /**
     * Flush the privileges for a given connection.
     */
    public function flush(): bool
    {
        return $this->run('FLUSH PRIVILEGES');
    }

    /**
     * Drop a given database on a specific connection.
     */
    public function dropDatabase(string $database): bool
    {
        return $this->run(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }

    /**
     * Drop a given user on a specific connection.
     */
    public function dropUser(string $username, string $remote): bool
    {
        return $this->run(sprintf('DROP USER IF EXISTS `%s`@`%s`', $username, $remote));
    }
}
