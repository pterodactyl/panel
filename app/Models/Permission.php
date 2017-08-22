<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
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
            'set-connection' => null,
            'view-startup' => null,
            'edit-startup' => null,
        ],
        'sftp' => [
            'view-sftp' => null,
            'view-sftp-password' => null,
            'reset-sftp' => 's:set-password',
        ],
        'file' => [
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
            'download-files' => null,
        ],
        'task' => [
            'list-tasks' => null,
            'view-task' => null,
            'toggle-task' => null,
            'queue-task' => null,
            'create-task' => null,
            'delete-task' => null,
        ],
        'database' => [
            'view-databases' => null,
            'reset-db-password' => null,
        ],
    ];

    /**
     * Return a collection of permissions available.
     *
     * @param array $single
     * @return \Illuminate\Support\Collection|array
     */
    public static function listPermissions($single = false)
    {
        if ($single) {
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
