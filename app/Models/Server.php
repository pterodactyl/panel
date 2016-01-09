<?php

namespace Pterodactyl\Models;

use Auth;

use Pterodactyl\Models\Subuser;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{

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
    protected $hidden = ['daemonSecret'];

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'installed', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected static $serverUUIDInstance = [];

    /**
     * @var mixed
     */
    protected static $user;

    /**
     * Constructor
     */
    public function __construct()
    {
        self::$user = Auth::user();
    }

    /**
     * Determine if we need to change the server's daemonSecret value to
     * match that of the user if they are a subuser.
     *
     * @param Illuminate\Database\Eloquent\Model\Server $server
     * @return string
     */
    protected static function getUserDaemonSecret(Server $server)
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
     * Returns all active servers if user is a root admin.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserServers($paginate = null)
    {

        $query = self::select('servers.*', 'nodes.name as nodeName', 'locations.short as a_locationShort')
                    ->join('nodes', 'servers.node', '=', 'nodes.id')
                    ->join('locations', 'nodes.location', '=', 'locations.id')
                    ->where('active', 1);

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Subuser::accessServers());
        }

        if (is_numeric($paginate)) {
            return $query->paginate($paginate);
        }

        return $query->get();

    }

    /**
     * Returns a single server specified by UUID
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
            ->where('uuidShort', $uuid)->where('active', 1);

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Subuser::accessServers());
        }

        $result = $query->first();

        if(!is_null($result)) {
            $result->daemonSecret = self::getUserDaemonSecret($result);
        }

        // Prevent saving of model called in this manner.
        // Prevents accidental overwrite of main daemon secret.
        $result::saving(function () {
            return false;
        });

        // Prevent deleting this model call.
        $result::deleting(function () {
            return false;
        });

        self::$serverUUIDInstance[$uuid] = $result;
        return self::$serverUUIDInstance[$uuid];

    }

    /**
     * Returns non-administrative headers for accessing a server on Scales
     *
     * @param  string $uuid
     * @return array
     */
    public static function getGuzzleHeaders($uuid)
    {

        if (array_key_exists($uuid, self::$serverUUIDInstance)) {
            return [
                'X-Access-Server' => self::$serverUUIDInstance[$uuid]->uuid,
                'X-Access-Token' => self::$serverUUIDInstance[$uuid]->daemonSecret
            ];
        }

        return [];

    }

}
