<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Permission extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'subuser_permission';

    /**
     * Should timestamps be used on this model.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'subuser_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'subuser_id' => 'required',
        'permission' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'subuser_id' => 'numeric|min:1',
        'permission' => 'string',
    ];

    /**
     * A list of all permissions available for a user.
     *
     * @var array
     */
    protected static $permissions = [
        'power' => [
            'power-start' => 's:power:start',
            'power-stop' => 's:power:stop',
            'power-restart' => 's:power:restart',
            'power-kill' => 's:power:kill',
            'send-command' => 's:command',
        ],
        'subuser' => [
            'list-subusers' => null,
            'view-subuser' => null,
            'edit-subuser' => null,
            'create-subuser' => null,
            'delete-subuser' => null,
        ],
        'server' => [
            'view-allocations' => null,
            'edit-allocation' => null,
            'view-startup' => null,
            'edit-startup' => null,
        ],
        'database' => [
            'view-databases' => null,
            'reset-db-password' => null,
            'delete-database' => null,
            'create-database' => null,
        ],
        'file' => [
            'access-sftp' => null,
            'list-files' => 's:files:get',
            'edit-files' => 's:files:read',
            'save-files' => 's:files:post',
            'move-files' => 's:files:move',
            'copy-files' => 's:files:copy',
            'compress-files' => 's:files:compress',
            'decompress-files' => 's:files:decompress',
            'create-files' => 's:files:create',
            'upload-files' => 's:files:upload',
            'delete-files' => 's:files:delete',
            'download-files' => 's:files:download',
        ],
        'task' => [
            'list-schedules' => null,
            'view-schedule' => null,
            'toggle-schedule' => null,
            'queue-schedule' => null,
            'edit-schedule' => null,
            'create-schedule' => null,
            'delete-schedule' => null,
        ],
    ];

    /**
     * Return a collection of permissions available.
     *
     * @param bool $array
     * @return array|\Illuminate\Support\Collection
     */
    public static function getPermissions($array = false)
    {
        if ($array) {
            return collect(self::$permissions)->mapWithKeys(function ($item) {
                return $item;
            })->all();
        }

        return collect(self::$permissions);
    }

    /**
     * Find permission by permission node.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string                             $permission
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopePermission($query, $permission)
    {
        return $query->where('permission', $permission);
    }

    /**
     * Filter permission by server.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Pterodactyl\Models\Server         $server
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeServer($query, Server $server)
    {
        return $query->where('server_id', $server->id);
    }
}
