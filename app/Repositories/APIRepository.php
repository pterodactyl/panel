<?php

namespace Pterodactyl\Repositories;

use DB;
use Crypt;
use Validator;
use IPTools\Network;

use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class APIRepository
{

    /**
     * Valid API permissions.
     * @var array
     */
    protected $permissions = [
        '*',

        // User Management Routes
        'api.users',
        'api.users.view',
        'api.users.post',
        'api.users.delete',
        'api.users.patch',

        // Server Manaement Routes
        'api.servers',
        'api.servers.view',
        'api.servers.post',
        'api.servers.suspend',
        'api.servers.unsuspend',
        'api.servers.delete',

        // Node Management Routes
        'api.nodes',
        'api.nodes.view',
        'api.nodes.post',
        'api.nodes.view_allocations',
        'api.nodes.delete',

        // Assorted Routes
        'api.services',
        'api.services.view',
        'api.locations',
    ];

    /**
     * Holder for listing of allowed IPs when creating a new key.
     * @var array
     */
    protected $allowed = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        //
    }

    /**
     * Create a New API Keypair on the system.
     *
     * @param  array $data An array with a permissions and allowed_ips key.
     *
     * @throws Pterodactyl\Exceptions\DisplayException if there was an error that can be safely displayed to end-users.
     * @throws Pterodactyl\Exceptions\DisplayValidationException if there was a validation error.
     *
     * @return string Returns the generated secret token.
     */
    public function new(array $data)
    {
        $validator = Validator::make($data, [
            'permissions' => 'required|array'
        ]);

        $validator->after(function($validator) use ($data) {
            if (array_key_exists('allowed_ips', $data) && !empty($data['allowed_ips'])) {
                foreach(explode("\n", $data['allowed_ips']) as $ip) {
                    $ip = trim($ip);
                    try {
                        Network::parse($ip);
                        array_push($this->allowed, $ip);
                    } catch (\Exception $ex) {
                        $validator->errors()->add('allowed_ips', 'Could not parse IP <' . $ip . '> because it is in an invalid format.');
                    }
                }
            }
        });

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();

        $secretKey = str_random(16) . '.' . str_random(15);
        $key = new Models\APIKey;
        $key->fill([
            'public' => str_random(16),
            'secret' => Crypt::encrypt($secretKey),
            'allowed_ips' => empty($this->allowed) ? null : json_encode($this->allowed)
        ]);
        $key->save();

        foreach($data['permissions'] as $permission) {
            if (in_array($permission, $this->permissions)) {
                $model = new Models\APIPermission;
                $model->fill([
                    'key_id' => $key->id,
                    'permission' => $permission
                ]);
                $model->save();
            }
        }

        try {
            DB::commit();
            return $secretKey;
        } catch (\Exception $ex) {
            throw $ex;
        }

    }

    /**
     * Revokes an API key and associated permissions.
     *
     * @param  string $key The public key.
     *
     * @throws Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @return void
     */
    public function revoke(string $key)
    {
        DB::beginTransaction();

        $model = Models\APIKey::where('public', $key)->firstOrFail();
        $permissions = Models\APIPermission::where('key_id', $model->id)->delete();
        $model->delete();

        DB::commit();
    }

}
