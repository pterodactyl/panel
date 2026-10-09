<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\MountFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property string $source
 * @property string $target
 * @property bool $read_only
 * @property bool $user_mountable
 * @property Egg[]|Collection $eggs
 * @property Node[]|Collection $nodes
 * @property Server[]|Collection $servers
 */
#[Attributes\Identifiable('moun')]
#[Guarded(['id', 'uuid'])]
#[WithoutTimestamps]
class Mount extends Model implements Identifiable
{
    /** @use HasFactory<MountFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'mount';

    /**
     * Blacklisted source paths.
     *
     * @var list<string>
     */
    public static array $invalidSourcePaths = [
        '/etc/pterodactyl',
        '/var/lib/pterodactyl/volumes',
        '/srv/daemon-data',
    ];

    /**
     * Source paths that cannot be mounted, nor anything beneath them.
     *
     * @var list<string>
     */
    public static array $protectedSourcePaths = [
        '/etc/pterodactyl',
    ];

    /**
     * Blacklisted target paths.
     *
     * @var list<string>
     */
    public static array $invalidTargetPaths = [
        '/home/container',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'int',
            'read_only' => 'bool',
            'user_mountable' => 'bool',
        ];
    }

    /**
     * Normalizes an absolute path string by resolving '.', '..', and duplicate slashes
     * without requiring the path to exist on the host filesystem.
     */
    public static function normalizePath(string $path): string
    {
        $parts = [];

        foreach (Str::of($path)->trim('/')->explode('/') as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($parts);

                continue;
            }

            $parts[] = $segment;
        }

        return '/'.implode('/', $parts);
    }

    /**
     * Returns all eggs that have this mount assigned.
     *
     * @return BelongsToMany<Egg, $this>
     */
    public function eggs(): BelongsToMany
    {
        return $this->belongsToMany(Egg::class);
    }

    /**
     * Returns all nodes that have this mount assigned.
     *
     * @return BelongsToMany<Node, $this>
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(Node::class);
    }

    /**
     * Returns all servers that have this mount assigned.
     *
     * @return BelongsToMany<Server, $this>
     */
    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class);
    }

    /**
     * Mounts attached to both the server's egg and its node, whether or not the
     * server currently has them mounted.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function availableToServer(Builder $query, Server $server): void
    {
        $query
            ->whereHas('eggs', fn (Builder $eggs) => $eggs->whereKey($server->egg_id))
            ->whereHas('nodes', fn (Builder $nodes) => $nodes->whereKey($server->node_id));
    }
}
