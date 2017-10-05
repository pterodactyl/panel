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

namespace Pterodactyl\Repositories;

use DB;
use Auth;
use Crypt;
use Validator;
use IPTools\Network;
use Pterodactyl\Models\User;
use Pterodactyl\Models\APIKey as Key;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\APIPermission as Permission;
use Pterodactyl\Exceptions\DisplayValidationException;

class APIRepository
{
    /**
     * Holder for listing of allowed IPs when creating a new key.
     *
     * @var array
     */
    protected $allowed = [];

    /**
     * The eloquent model for a user.
     *
     * @var \Pterodactyl\Models\User
     */
    protected $user;

    /**
     * Constructor for API Repository.
     *
     * @param  null|\Pterodactyl\Models\User  $user
     * @return void
     */
    public function __construct(User $user = null)
    {
        $this->user = is_null($user) ? Auth::user() : $user;
        if (is_null($this->user)) {
            throw new \Exception('Unable to initialize user for API repository instance.');
        }
    }

    /**
     * Create a New API Keypair on the system.
     *
     * @param  array  $data
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'memo' => 'string|max:500',
            'allowed_ips' => 'sometimes|string',
            'permissions' => 'sometimes|required|array',
            'admin_permissions' => 'sometimes|required|array',
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
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        DB::beginTransaction();
        try {
            $secretKey = str_random(16) . '.' . str_random(7) . '.' . str_random(7);
            $key = Key::create([
                'user_id' => $this->user->id,
                'public' => str_random(16),
                'secret' => Crypt::encrypt($secretKey),
                'allowed_ips' => empty($this->allowed) ? null : json_encode($this->allowed),
                'memo' => $data['memo'],
                'expires_at' => null,
            ]);

            $totalPermissions = 0;
            $pNodes = Permission::permissions();

            if (isset($data['permissions'])) {
                foreach ($data['permissions'] as $permission) {
                    $parts = explode('-', $permission);

                    if (count($parts) !== 2) {
                        continue;
                    }

                    list($block, $search) = $parts;

                    if (! array_key_exists($block, $pNodes['_user'])) {
                        continue;
                    }

                    if (! in_array($search, $pNodes['_user'][$block])) {
                        continue;
                    }

                    $totalPermissions++;
                    Permission::create([
                        'key_id' => $key->id,
                        'permission' => 'user.' . $permission,
                    ]);
                }
            }

            if ($this->user->isRootAdmin() && isset($data['admin_permissions'])) {
                unset($pNodes['_user']);

                foreach ($data['admin_permissions'] as $permission) {
                    $parts = explode('-', $permission);

                    if (count($parts) !== 2) {
                        continue;
                    }

                    list($block, $search) = $parts;

                    if (! array_key_exists($block, $pNodes)) {
                        continue;
                    }

                    if (! in_array($search, $pNodes[$block])) {
                        continue;
                    }

                    $totalPermissions++;
                    Permission::create([
                        'key_id' => $key->id,
                        'permission' => $permission,
                    ]);
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
     * @param  string  $key
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function revoke($key)
    {
        DB::transaction(function () use ($key) {
            $model = Key::with('permissions')->where('public', $key)->where('user_id', $this->user->id)->firstOrFail();
            foreach ($model->permissions as &$permission) {
                $permission->delete();
            }

            $model->delete();
        });
    }
}
