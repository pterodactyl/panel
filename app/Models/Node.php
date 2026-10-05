<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\NodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Location $location
 * @property Collection<int, Mount> $mounts
 * @property Server[]|Collection $servers
 * @property Allocation[]|Collection $allocations
 */
#[Attributes\Identifiable('node')]
#[Fillable([
    'public', 'name', 'location_id',
    'description', 'fqdn', 'scheme', 'behind_proxy',
    'memory', 'memory_overallocate', 'disk',
    'disk_overallocate', 'upload_size', 'daemonBase',
    'daemonSFTP', 'daemonListen',
    'maintenance_mode',
])]
#[Hidden(['daemon_token_id', 'daemon_token'])]
class Node extends Model implements Identifiable
{
    /** @use HasFactory<NodeFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;
    use HasVersion4Uuids;
    use Notifiable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'node';

    public const int DAEMON_TOKEN_ID_LENGTH = 16;

    public const int DAEMON_TOKEN_LENGTH = 64;

    /** Utilization at or below this percentage renders green. */
    public const int THRESHOLD_PERCENTAGE_LOW = 75;

    /** Utilization above this percentage renders red; between the two, yellow. */
    public const int THRESHOLD_PERCENTAGE_MEDIUM = 90;

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'memory' => 'integer',
            'memory_overallocate' => 'integer',
            'disk' => 'integer',
            'disk_overallocate' => 'integer',
            'upload_size' => 'integer',
            'daemonListen' => 'integer',
            'daemonSFTP' => 'integer',
            'behind_proxy' => 'boolean',
            'public' => 'boolean',
            'maintenance_mode' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the connection address to use when making calls to this node.
     */
    public function getConnectionAddress(): string
    {
        return sprintf('%s://%s:%s', $this->scheme, $this->fqdn, $this->daemonListen);
    }

    /**
     * Returns the configuration as an array.
     *
     * @return NodeConfiguration the wings configuration blob for this node
     */
    public function getConfiguration(): array
    {
        $allowedMounts = [];
        foreach ($this->mounts as $mount) {
            $allowedMounts[] = $mount->source;
        }

        return [
            'debug' => false,
            'uuid' => $this->uuid,
            'token_id' => $this->daemon_token_id,
            'token' => $this->getDecryptedKey(),
            'api' => [
                'host' => '0.0.0.0',
                'port' => $this->daemonListen,
                'ssl' => [
                    'enabled' => (! $this->behind_proxy && $this->scheme === 'https'),
                    'cert' => '/etc/letsencrypt/live/'.Str::lower($this->fqdn).'/fullchain.pem',
                    'key' => '/etc/letsencrypt/live/'.Str::lower($this->fqdn).'/privkey.pem',
                ],
                'upload_limit' => $this->upload_size,
            ],
            'system' => [
                'data' => $this->daemonBase,
                'sftp' => [
                    'bind_port' => $this->daemonSFTP,
                ],
            ],
            'allowed_mounts' => $allowedMounts,
            'remote' => mb_rtrim(JsonValueGuard::nonEmptyString(config('app.url')), '/'),
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
        return json_encode(
            $this->getConfiguration(),
            ($pretty ? JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT : JSON_UNESCAPED_SLASHES) | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Helper function to return the decrypted key for a node.
     */
    public function getDecryptedKey(): string
    {
        $key = Crypt::decrypt($this->daemon_token);
        throw_unless(is_string($key), UnexpectedValueException::class, 'The decrypted node key must be a string.');

        return $key;
    }

    public function isUnderMaintenance(): bool
    {
        return $this->maintenance_mode;
    }

    /**
     * @return HasManyThrough<Mount, MountNode, $this>
     */
    public function mounts(): HasManyThrough
    {
        return $this->hasManyThrough(Mount::class, MountNode::class, 'node_id', 'id', 'id', 'mount_id');
    }

    /**
     * Gets the location associated with a node.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Gets the servers associated with a node.
     *
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Gets the allocations associated with a node.
     *
     * @return HasMany<Allocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /**
     * The tags on this node, under either pivot kind. Present for administrative
     * listings; the deploy gate reads the kind-scoped eggTags()/deploymentTags()
     * instead.
     *
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id');
    }

    /**
     * The games this node accepts, OR-matched against a deploying egg's tags.
     *
     * withPivotValue both scopes reads and stamps the kind on attach/sync, so
     * syncing one kind never disturbs the other's rows.
     *
     * @return MorphToMany<Tag, $this>
     */
    public function eggTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id')
            ->withPivotValue('kind', 'egg');
    }

    /**
     * The reservations on this node: a deploy lands here only when its own deploy
     * tags match these exactly (see NodeTagGate).
     *
     * @return MorphToMany<Tag, $this>
     */
    public function deploymentTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id')
            ->withPivotValue('kind', 'deployment');
    }

    /**
     * @return string[]
     */
    public function eggTagSlugs(): array
    {
        return JsonValueGuard::stringList($this->eggTags->pluck('slug')->all());
    }

    /**
     * @return string[]
     */
    public function deploymentTagSlugs(): array
    {
        return JsonValueGuard::stringList($this->deploymentTags->pluck('slug')->all());
    }

    /**
     * Whether an additional server of the given size fits within this node's
     * (over)allocatable memory and disk.
     */
    public function isViable(int $memory, int $disk): bool
    {
        $used = $this->allocatedResources();
        $memoryLimit = $this->memory * (1 + ($this->memory_overallocate / 100));
        $diskLimit = $this->disk * (1 + ($this->disk_overallocate / 100));

        return ($used['memory'] + $memory) <= $memoryLimit && ($used['disk'] + $disk) <= $diskLimit;
    }

    /**
     * Memory and disk consumed by this node's servers against its (over)allocatable
     * capacity, in the shape the admin utilization endpoint returns.
     *
     * @return array<string, array{value: string, max: string, percent: float|int, css: string}>
     */
    public function utilization(): array
    {
        $used = $this->allocatedResources();

        return [
            'disk' => $this->utilizationOf($used['disk'], $this->disk, $this->disk_overallocate),
            'memory' => $this->utilizationOf($used['memory'], $this->memory, $this->memory_overallocate),
        ];
    }

    /** @return array{memory: int, disk: int} */
    public function allocatedResources(): array
    {
        if (array_key_exists('sum_memory', $this->getAttributes()) && array_key_exists('sum_disk', $this->getAttributes())) {
            // SAFETY: these aliases are database SUM projections and therefore integer-like.
            $sumMemory = $this->getAttribute('sum_memory');
            $memory = $sumMemory === null ? 0 : JsonValueGuard::integer($sumMemory);
            // SAFETY: these aliases are database SUM projections and therefore integer-like.
            $sumDisk = $this->getAttribute('sum_disk');
            $disk = $sumDisk === null ? 0 : JsonValueGuard::integer($sumDisk);
        } else {
            $resources = $this->servers()->toBase()
                ->selectRaw('COALESCE(SUM(memory), 0) AS memory, COALESCE(SUM(disk), 0) AS disk')
                ->first();

            throw_if($resources === null || ! is_numeric($resources->memory) || ! is_numeric($resources->disk), UnexpectedValueException::class, 'The node resource aggregate query returned malformed values.');

            // SAFETY: the aggregate values were validated as numeric immediately above.
            $memory = (int) $resources->memory;
            // SAFETY: the aggregate values were validated as numeric immediately above.
            $disk = (int) $resources->disk;
        }

        return ['memory' => $memory, 'disk' => $disk];
    }

    /** @return array{value: string, max: string, percent: float|int, css: string} */
    private function utilizationOf(int $used, int $capacity, int $overallocate): array
    {
        $max = $overallocate > 0 ? $capacity * (1 + ($overallocate / 100)) : $capacity;
        $percent = $max > 0 ? ($used / $max) * 100 : 0;

        return [
            'value' => number_format($used),
            'max' => number_format($max),
            'percent' => $percent,
            'css' => match (true) {
                $percent <= self::THRESHOLD_PERCENTAGE_LOW => 'green',
                $percent > self::THRESHOLD_PERCENTAGE_MEDIUM => 'red',
                default => 'yellow',
            },
        ];
    }
}
