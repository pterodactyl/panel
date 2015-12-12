<?php

namespace Pterodactyl\Models;

use Auth;
use DB;
use Debugbar;
use Validator;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Exceptions\DisplayValidationException;

use Pterodactyl\Models;

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

    protected static function generateSFTPUsername($name)
    {

        $name = preg_replace('/\s+/', '', $name);
        if (strlen($name) > 6) {
            return strtolower('ptdl-' . substr($name, 0, 6) . '_' . str_random(5));
        }

        return strtolower('ptdl-' . $name . '_' . str_random((11 - strlen($name))));

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

        $subuser = Models\Subuser::where('server_id', $server->id)->where('user_id', self::$user->id)->first();

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
    public static function getUserServers()
    {

        $query = self::select('servers.*', 'nodes.name as nodeName', 'locations.long as location')
                    ->join('nodes', 'servers.node', '=', 'nodes.id')
                    ->join('locations', 'nodes.location', '=', 'locations.id')
                    ->where('active', 1);

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Models\Subuser::accessServers());
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

        $query = self::where('uuidShort', $uuid)->where('active', 1);

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Models\Subuser::accessServers());
        }

        $result = $query->first();

        if(!is_null($result)) {
            $result->daemonSecret = self::getUserDaemonSecret($result);
        }

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

    /**
     * Adds a new server to the system.
     * @param array  $data  An array of data descriptors for creating the server. These should align to the columns in the database.
     */
    public static function addServer(array $data)
    {

        // Validate Fields
        $validator = Validator::make($data, [
            'owner' => 'required|email|exists:users,email',
            'node' => 'required|numeric|min:1|exists:nodes,id',
            'name' => 'required|regex:([\w -]{4,35})',
            'memory' => 'required|numeric|min:1',
            'disk' => 'required|numeric|min:1',
            'cpu' => 'required|numeric|min:0',
            'io' => 'required|numeric|min:10|max:1000',
            'ip' => 'required|ip',
            'port' => 'required|numeric|min:1|max:65535',
            'service' => 'required|numeric|min:1|exists:services,id',
            'option' => 'required|numeric|min:1|exists:service_options,id',
            'custom_image_name' => 'required_if:use_custom_image,on',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()->all()));
        }

        // Get the User ID; user exists since we passed the 'exists:users,email' part of the validation
        $user = Models\User::select('id')->where('email', $data['owner'])->first();

        // Verify IP & Port are a.) free and b.) assigned to the node.
        // We know the node exists because of 'exists:nodes,id' in the validation
        $node = Models\Node::find($data['node']);
        $allocation = Models\Allocation::where('ip', $data['ip'])->where('port', $data['port'])->where('node', $data['node'])->whereNull('assigned_to')->first();

        // Something failed in the query, either that combo doesn't exist, or it is in use.
        if (!$allocation) {
            throw new DisplayException('The selected IP/Port combination (' . $data['ip'] . ':' . $data['port'] . ') is either already in use, or unavaliable for this node.');
        }

        // Validate those Service Option Variables
        // We know the service and option exists because of the validation.
        // We need to verify that the option exists for the service, and then check for
        // any required variable fields. (fields are labeled env_<env_variable>)
        $option = Models\ServiceOptions::where('id', $data['option'])->where('parent_service', $data['service'])->first();
        if (!$option) {
            throw new DisplayException('The requested service option does not exist for the specified service.');
        }

        // Check those Variables
        $variables = Models\ServiceVariables::where('option_id', $data['option'])->get();
        if ($variables) {
            foreach($variables as $variable) {

                // Is the variable required?
                if (!$data['env_' . $variable->env_variable]) {
                    if ($variable->required === 1) {
                        throw new DisplayException('A required service option variable field (env_' . $variable->env_variable . ') was missing from the request.');
                    }

                    $data['env_' . $variable->env_variable] = $variable->default_value;
                    continue;
                }

                // Check aganist Regex Pattern
                if (!is_null($variable->regex) && !preg_match($variable->regex, $data['env_' . $variable->env_variable])) {
                    throw new DisplayException('Failed to validate service option variable field (env_' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                }

                continue;

            }
        }

        return self::generateSFTPUsername($data['name']);

    }

}
