<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>.
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

namespace Pterodactyl\Repositories;

use DB;
use Auth;
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
        'admin' => [
            '*',

            // User Management Routes
            'users.list',
            'users.create',
            'users.view',
            'users.update',
            'users.delete',

            // Server Manaement Routes
            'servers.list',
            'servers.create',
            'servers.view',
            'servers.config',
            'servers.build',
            'servers.suspend',
            'servers.unsuspend',
            'servers.delete',

            // Node Management Routes
            'nodes.list',
            'nodes.create',
            'nodes.list',
            'nodes.allocations',
            'nodes.delete',

            // Service Routes
            'services.list',
            'services.view',

            // Location Routes
            'locations.list',

        ],
        'user' => [
            '*',

            // Informational
            'me',

            // Server Control
            'server',
            'server.power',
        ],
    ];

    /**
     * Holder for listing of allowed IPs when creating a new key.
     * @var array
     */
    protected $allowed = [];

    protected $user;

    /**
     * Constructor.
     */
    public function __construct(Models\User $user = null)
    {
        $this->user = is_null($user) ? Auth::user() : $user;
        if (is_null($this->user)) {
            throw new \Exception('Cannot access API Repository without passing a user to __construct().');
        }
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
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'memo' => 'string|max:500',
            'permissions' => 'sometimes|required|array',
            'adminPermissions' => 'sometimes|required|array',
        ]);

        $validator->after(function ($validator) use ($data) {
            if (array_key_exists('allowed_ips', $data) && ! empty($data['allowed_ips'])) {
                foreach (explode("\n", $data['allowed_ips']) as $ip) {
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
        try {
            $secretKey = str_random(16) . '.' . str_random(7) . '.' . str_random(7);
            $key = new Models\APIKey;
            $key->fill([
                'user' => $this->user->id,
                'public' => str_random(16),
                'secret' => Crypt::encrypt($secretKey),
                'allowed_ips' => empty($this->allowed) ? null : json_encode($this->allowed),
                'memo' => $data['memo'],
                'expires_at' => null,
            ]);
            $key->save();

            $totalPermissions = 0;
            if (isset($data['permissions'])) {
                foreach ($data['permissions'] as $permNode) {
                    if (! strpos($permNode, ':')) {
                        continue;
                    }

                    list($toss, $permission) = explode(':', $permNode);
                    if (in_array($permission, $this->permissions['user'])) {
                        $totalPermissions++;
                        $model = new Models\APIPermission;
                        $model->fill([
                            'key_id' => $key->id,
                            'permission' => 'api.user.' . $permission,
                        ]);
                        $model->save();
                    }
                }
            }

            if ($this->user->root_admin === 1 && isset($data['adminPermissions'])) {
                foreach ($data['adminPermissions'] as $permNode) {
                    if (! strpos($permNode, ':')) {
                        continue;
                    }

                    list($toss, $permission) = explode(':', $permNode);
                    if (in_array($permission, $this->permissions['admin'])) {
                        $totalPermissions++;
                        $model = new Models\APIPermission;
                        $model->fill([
                            'key_id' => $key->id,
                            'permission' => 'api.admin.' . $permission,
                        ]);
                        $model->save();
                    }
                }
            }

            if ($totalPermissions < 1) {
                throw new DisplayException('No valid permissions were passed.');
            }

            DB::commit();

            return $secretKey;
        } catch (\Exception $ex) {
            DB::rollBack();
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
    public function revoke($key)
    {
        DB::beginTransaction();

        try {
            $model = Models\APIKey::where('public', $key)->where('user', $this->user->id)->firstOrFail();
            $permissions = Models\APIPermission::where('key_id', $model->id)->delete();
            $model->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
