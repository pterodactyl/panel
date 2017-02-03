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
use Javascript;
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
         'node_id' => 'integer',
         'suspended' => 'integer',
         'owner_id' => 'integer',
         'memory' => 'integer',
         'swap' => 'integer',
         'disk' => 'integer',
         'io' => 'integer',
         'cpu' => 'integer',
         'oom_disabled' => 'integer',
         'allocation_id' => 'integer',
         'service_id' => 'integer',
         'option_id' => 'integer',
         'pack_id' => 'integer',
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
        )->join('nodes', 'servers.node_id', '=', 'nodes.id')
        ->join('locations', 'nodes.location_id', '=', 'locations.id')
        ->join('services', 'servers.service_id', '=', 'services.id')
        ->join('service_options', 'servers.option_id', '=', 'service_options.id')
        ->join('allocations', 'servers.allocation_id', '=', 'allocations.id');

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
    public static function byUuid($uuid)
    {
        $query = self::with('service', 'node')->where('uuidShort', $uuid)->orWhere('uuid', $uuid);

        if (! Auth::user()->isRootAdmin()) {
            $query->whereIn('id', Subuser::accessServers());
        }

        $result = $query->first();

        if (! is_null($result)) {
            $result->daemonSecret = Auth::user()->daemonToken($result);
        }

        return $result;
    }

    /**
     * Returns non-administrative headers for accessing a server on the daemon.
     *
     * @param  string $uuid
     * @return array
     */
    public function guzzleHeaders()
    {
        return [
            'X-Access-Server' => $this->uuid,
            'X-Access-Token' => Auth::user()->daemonToken($this),
        ];
    }

    /**
     * Return an instance of the Guzzle client for this specific server using defined access token.
     *
     * @return \GuzzleHttp\Client
     */
    public function guzzleClient()
    {
        return $this->node->guzzleClient($this->guzzleHeaders());
    }

    /**
     * Returns javascript object to be embedded on server view pages with relevant information.
     *
     * @return \Laracasts\Utilities\JavaScript\JavaScriptFacade
     */
    public function js($additional = null, $overwrite = null)
    {
        $response = [
            'server' => collect($this->makeVisible('daemonSecret'))->only([
                'uuid',
                'uuidShort',
                'daemonSecret',
                'username',
            ]),
            'node' => collect($this->node)->only([
                'fqdn',
                'scheme',
                'daemonListen',
            ]),
        ];

        if (is_array($additional)) {
            $response = array_merge($response, $additional);
        }

        if (is_array($overwrite)) {
            $response = $overwrite;
        }

        return Javascript::put($response);
    }

    /**
     * Gets the user who owns the server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'owner_id');
    }

    /**
     * Gets all allocations associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(Allocation::class, 'server_id');
    }

    /**
     * Gets information for the pack associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pack()
    {
        return $this->hasOne(ServicePack::class, 'id', 'pack_id');
    }

    /**
     * Gets information for the service associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function service()
    {
        return $this->hasOne(Service::class, 'id', 'service_id');
    }

    /**
     * Gets information for the service option associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function option()
    {
        return $this->hasOne(ServiceOptions::class, 'id', 'option_id');
    }

    /**
     * Gets information for the service variables associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(ServerVariables::class);
    }

    /**
     * Gets information for the node associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function node()
    {
        return $this->hasOne(Node::class, 'id', 'node_id');
    }

    /**
     * Gets information for the tasks associated with this server.
     *
     * @TODO adjust server column in tasks to be server_id
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'server', 'id');
    }
}
