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

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servers';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['daemonSecret', 'sftp_password'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'installed', 'created_at', 'updated_at', 'deleted_at'];

     /**
      * Cast values to correct type.
      *
      * @var array
      */
     protected $casts = [
         'node' => 'integer',
         'suspended' => 'integer',
         'owner' => 'integer',
         'memory' => 'integer',
         'swap' => 'integer',
         'disk' => 'integer',
         'io' => 'integer',
         'cpu' => 'integer',
         'oom_disabled' => 'integer',
         'port' => 'integer',
         'service' => 'integer',
         'option' => 'integer',
         'installed' => 'integer',
     ];

    /**
     * @var array
     */
    protected static $serverUUIDInstance = [];

    /**
     * @var mixed
     */
    protected static $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        self::$user = Auth::user();
    }

    /**
     * Determine if we need to change the server's daemonSecret value to
     * match that of the user if they are a subuser.
     *
     * @param Illuminate\Database\Eloquent\Model\Server $server
     * @return string
     */
    public static function getUserDaemonSecret(Server $server)
    {
        if (self::$user->id === $server->owner || self::$user->root_admin === 1) {
            return $server->daemonSecret;
        }

        $subuser = Subuser::where('server_id', $server->id)->where('user_id', self::$user->id)->first();

        if (is_null($subuser)) {
            return null;
        }

        return $subuser->daemonSecret;
    }

    /**
     * Returns array of all servers owned by the logged in user.
     * Returns all users servers if user is a root admin.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserServers($paginate = null)
    {
        $query = self::select(
            'servers.*',
            'nodes.name as nodeName',
            'locations.short as a_locationShort',
            'allocations.ip',
            'allocations.ip_alias',
            'allocations.port',
            'services.name as a_serviceName',
            'service_options.name as a_serviceOptionName'
        )->join('nodes', 'servers.node', '=', 'nodes.id')
        ->join('locations', 'nodes.location', '=', 'locations.id')
        ->join('services', 'servers.service', '=', 'services.id')
        ->join('service_options', 'servers.option', '=', 'service_options.id')
        ->join('allocations', 'servers.allocation', '=', 'allocations.id');

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Subuser::accessServers());
        }

        if (is_numeric($paginate)) {
            return $query->paginate($paginate);
        }

        return $query->get();
    }

    /**
     * Returns a single server specified by UUID.
     * DO NOT USE THIS TO MODIFY SERVER DETAILS OR SAVE THOSE DETAILS.
     * YOU WILL OVERWRITE THE SECRET KEY AND BREAK THINGS.
     *
     * @param  string $uuid The Short-UUID of the server to return an object about.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByUUID($uuid)
    {
        if (array_key_exists($uuid, self::$serverUUIDInstance)) {
            return self::$serverUUIDInstance[$uuid];
        }

        $query = self::select('servers.*', 'services.file as a_serviceFile')
            ->join('services', 'services.id', '=', 'servers.service')
            ->where('uuidShort', $uuid)
            ->orWhere('uuid', $uuid);

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Subuser::accessServers());
        }

        $result = $query->first();

        if (! is_null($result)) {
            $result->daemonSecret = self::getUserDaemonSecret($result);
        }

        self::$serverUUIDInstance[$uuid] = $result;

        return self::$serverUUIDInstance[$uuid];
    }

    /**
     * Returns non-administrative headers for accessing a server on the daemon.
     *
     * @param  string $uuid
     * @return array
     */
    public static function getGuzzleHeaders($uuid)
    {
        if (array_key_exists($uuid, self::$serverUUIDInstance)) {
            return [
                'X-Access-Server' => self::$serverUUIDInstance[$uuid]->uuid,
                'X-Access-Token' => self::$serverUUIDInstance[$uuid]->daemonSecret,
            ];
        }

        return [];
    }
}
