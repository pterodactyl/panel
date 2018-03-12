<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Node extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Notifiable, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'node';

    const DAEMON_SECRET_LENGTH = 36;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nodes';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['daemonSecret'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'public' => 'integer',
        'location_id' => 'integer',
        'memory' => 'integer',
        'disk' => 'integer',
        'daemonListen' => 'integer',
        'daemonSFTP' => 'integer',
        'behind_proxy' => 'boolean',
    ];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'public', 'name', 'location_id',
        'fqdn', 'scheme', 'behind_proxy',
        'memory', 'memory_overallocate', 'disk',
        'disk_overallocate', 'upload_size',
        'daemonSecret', 'daemonBase',
        'daemonSFTP', 'daemonListen',
    ];

    /**
     * Fields that are searchable.
     *
     * @var array
     */
    protected $searchableColumns = [
        'name' => 10,
        'fqdn' => 8,
        'location.short' => 4,
        'location.long' => 4,
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'name' => 'required',
        'location_id' => 'required',
        'fqdn' => 'required',
        'scheme' => 'required',
        'memory' => 'required',
        'memory_overallocate' => 'required',
        'disk' => 'required',
        'disk_overallocate' => 'required',
        'daemonBase' => 'sometimes|required',
        'daemonSFTP' => 'required',
        'daemonListen' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'name' => 'regex:/^([\w .-]{1,100})$/',
        'location_id' => 'exists:locations,id',
        'public' => 'boolean',
        'fqdn' => 'string',
        'behind_proxy' => 'boolean',
        'memory' => 'numeric|min:1',
        'memory_overallocate' => 'numeric|min:-1',
        'disk' => 'numeric|min:1',
        'disk_overallocate' => 'numeric|min:-1',
        'daemonBase' => 'regex:/^([\/][\d\w.\-\/]+)$/',
        'daemonSFTP' => 'numeric|between:1024,65535',
        'daemonListen' => 'numeric|between:1024,65535',
    ];

    /**
     * Default values for specific columns that are generally not changed on base installs.
     *
     * @var array
     */
    protected $attributes = [
        'public' => true,
        'behind_proxy' => false,
        'memory_overallocate' => 0,
        'disk_overallocate' => 0,
        'daemonBase' => '/srv/daemon-data',
        'daemonSFTP' => 2022,
        'daemonListen' => 8080,
    ];

    /**
     * Returns the configuration in JSON format.
     *
     * @param bool $pretty
     * @return string
     */
    public function getConfigurationAsJson($pretty = false)
    {
        $config = [
            'web' => [
                'host' => '0.0.0.0',
                'listen' => $this->daemonListen,
                'ssl' => [
                    'enabled' => (! $this->behind_proxy && $this->scheme === 'https'),
                    'certificate' => '/etc/letsencrypt/live/' . $this->fqdn . '/fullchain.pem',
                    'key' => '/etc/letsencrypt/live/' . $this->fqdn . '/privkey.pem',
                ],
            ],
            'docker' => [
                'container' => [
                    'user' => null,
                ],
                'network' => [
                    'name' => 'pterodactyl_nw',
                ],
                'socket' => '/var/run/docker.sock',
                'autoupdate_images' => true,
            ],
            'filesystem' => [
                'server_logs' => '/tmp/pterodactyl',
            ],
            'internals' => [
                'disk_use_seconds' => 30,
                'set_permissions_on_boot' => true,
                'throttle' => [
                    'enabled' => true,
                    'kill_at_count' => 5,
                    'decay' => 10,
                    'lines' => 1000,
                    'check_interval_ms' => 100,
                ],
            ],
            'sftp' => [
                'path' => $this->daemonBase,
                'ip' => '0.0.0.0',
                'port' => $this->daemonSFTP,
                'keypair' => [
                    'bits' => 2048,
                    'e' => 65537,
                ],
            ],
            'logger' => [
                'path' => 'logs/',
                'src' => false,
                'level' => 'info',
                'period' => '1d',
                'count' => 3,
            ],
            'remote' => [
                'base' => route('index'),
            ],
            'uploads' => [
                'size_limit' => $this->upload_size,
            ],
            'keys' => [$this->daemonSecret],
        ];

        return json_encode($config, ($pretty) ? JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT : JSON_UNESCAPED_SLASHES);
    }

    /**
     * Gets the location associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Gets the servers associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Gets the allocations associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }
}
