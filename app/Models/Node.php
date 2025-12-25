<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Container\Container;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $uuid
 * @property bool $public
 * @property string $name
 * @property string|null $description
 * @property int $location_id
 * @property string $fqdn
 * @property string $scheme
 * @property bool $behind_proxy
 * @property bool $maintenance_mode
 * @property int $memory
 * @property int $memory_overallocate
 * @property int $disk
 * @property int $disk_overallocate
 * @property int $upload_size
 * @property string $daemon_token_id
 * @property string $daemon_token
 * @property int $daemonListen
 * @property int $daemonSFTP
 * @property string $daemonBase
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Location $location
 * @property \Pterodactyl\Models\Mount[]|\Illuminate\Database\Eloquent\Collection $mounts
 * @property \Pterodactyl\Models\Server[]|\Illuminate\Database\Eloquent\Collection $servers
 * @property \Pterodactyl\Models\Allocation[]|\Illuminate\Database\Eloquent\Collection $allocations
 */
class Node extends Model
{
    /** @use HasFactory<\Database\Factories\NodeFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'node';

    public const DAEMON_TOKEN_ID_LENGTH = 16;
    public const DAEMON_TOKEN_LENGTH = 64;

    /**
     * The table associated with the model.
     */
    protected $table = 'nodes';

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = ['daemon_token_id', 'daemon_token'];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'location_id' => 'integer',
        'memory' => 'integer',
        'disk' => 'integer',
        'daemonListen' => 'integer',
        'daemonSFTP' => 'integer',
        'behind_proxy' => 'boolean',
        'public' => 'boolean',
        'maintenance_mode' => 'boolean',
    ];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'public', 'name', 'location_id',
        'description', 'fqdn', 'scheme', 'behind_proxy',
        'memory', 'memory_overallocate', 'disk',
        'disk_overallocate', 'upload_size', 'daemonBase',
        'daemonSFTP', 'daemonListen',
        'description', 'maintenance_mode',
    ];

    public static array $validationRules = [
        'name' => 'required|regex:/^([\w .-]{1,100})$/',
        'description' => 'string|nullable',
        'location_id' => 'required|exists:locations,id',
        'public' => 'boolean',
        'fqdn' => 'required|string',
        'scheme' => 'required',
        'behind_proxy' => 'boolean',
        'memory' => 'required|numeric|min:1',
        'memory_overallocate' => 'required|numeric|min:-1',
        'disk' => 'required|numeric|min:1',
        'disk_overallocate' => 'required|numeric|min:-1',
        'daemonBase' => 'sometimes|required|regex:/^([\/][\d\w.\-\/]+)$/',
        'daemonSFTP' => 'required|numeric|between:1,65535',
        'daemonListen' => 'required|numeric|between:1,65535',
        'maintenance_mode' => 'boolean',
        'upload_size' => 'int|between:1,1024',
    ];

    /**
     * Default values for specific columns that are generally not changed on base installs.
     */
    protected $attributes = [
        'public' => true,
        'behind_proxy' => false,
        'memory_overallocate' => 0,
        'disk_overallocate' => 0,
        'daemonBase' => '/var/lib/pterodactyl/volumes',
        'daemonSFTP' => 2022,
        'daemonListen' => 8080,
        'maintenance_mode' => false,
    ];

    /**
     * Get the connection address to use when making calls to this node.
     */
    public function getConnectionAddress(): string
    {
        return sprintf('%s://%s:%s', $this->scheme, $this->fqdn, $this->daemonListen);
    }

    /**
     * Returns the configuration as an array.
     */
    public function getConfiguration(): array
    {
        return [
            'debug' => false,
            'uuid' => $this->uuid,
            'token_id' => $this->daemon_token_id,
            'token' => Container::getInstance()->make(Encrypter::class)->decrypt($this->daemon_token),
            'api' => [
                'host' => '0.0.0.0',
                'port' => $this->daemonListen,
                'ssl' => [
                    'enabled' => (!$this->behind_proxy && $this->scheme === 'https'),
                    'cert' => '/etc/letsencrypt/live/' . Str::lower($this->fqdn) . '/fullchain.pem',
                    'key' => '/etc/letsencrypt/live/' . Str::lower($this->fqdn) . '/privkey.pem',
                ],
                'upload_limit' => $this->upload_size,
            ],
            'system' => [
                'data' => $this->daemonBase,
                'sftp' => [
                    'bind_port' => $this->daemonSFTP,
                ],
            ],
            'allowed_mounts' => $this->mounts->pluck('source')->toArray(),
            'remote' => route('index'),
        ];
    }

    /**
     * Returns the configuration in Yaml format.
     */
    public function getYamlConfiguration(): string
    {
        return Yaml::dump($this->getConfiguration(), 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
    }

    /**
     * Returns the configuration in JSON format.
     */
    public function getJsonConfiguration(bool $pretty = false): string
    {
        return json_encode($this->getConfiguration(), $pretty ? JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT : JSON_UNESCAPED_SLASHES);
    }

    /**
     * Helper function to return the decrypted key for a node.
     */
    public function getDecryptedKey(): string
    {
        return (string) Container::getInstance()->make(Encrypter::class)->decrypt(
            $this->daemon_token
        );
    }

    public function isUnderMaintenance(): bool
    {
        return $this->maintenance_mode;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\Pterodactyl\Models\Mount, \Pterodactyl\Models\MountNode, $this>
     */
    public function mounts(): HasManyThrough
    {
        return $this->hasManyThrough(Mount::class, MountNode::class, 'node_id', 'id', 'id', 'mount_id');
    }

    /**
     * Gets the location associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Pterodactyl\Models\Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Gets the servers associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Gets the allocations associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\Allocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /**
     * Returns a boolean if the node is viable for an additional server to be placed on it.
     */
    public function isViable(int $memory, int $disk): bool
    {
        $memoryLimit = $this->memory * (1 + ($this->memory_overallocate / 100));
        $diskLimit = $this->disk * (1 + ($this->disk_overallocate / 100));

        // @phpstan-ignore-next-line property.notFound, property.notFound
        return ($this->sum_memory + $memory) <= $memoryLimit && ($this->sum_disk + $disk) <= $diskLimit;
    }
}
