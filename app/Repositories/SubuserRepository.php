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
use Validator;
use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class SubuserRepository
{
    /**
     * Core permissions required for every subuser on the daemon.
     * Without this we cannot connect the websocket or get basic
     * information about the server.
     *
     * @var array
     */
    protected $coreDaemonPermissions = [
        's:get',
        's:console',
    ];

    /**
     * Creates a new subuser on the server.
     *
     * @param  int    $sid
     * @param  array  $data
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create($sid, array $data)
    {
        $server = Models\Server::with('node')->findOrFail($sid);

        $validator = Validator::make($data, [
            'permissions' => 'required|array',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        DB::beginTransaction();

        try {
            // Determine if this user exists or if we need to make them an account.
            $user = Models\User::where('email', $data['email'])->first();
            if (! $user) {
                try {
                    $repo = new UserRepository;
                    $user = $repo->create([
                        'email' => $data['email'],
                        'username' => str_random(8),
                        'name_first' => 'Unassigned',
                        'name_last' => 'Name',
                        'root_admin' => false,
                    ]);
                } catch (\Exception $ex) {
                    throw $ex;
                }
            } elseif ($server->owner_id === $user->id) {
                throw new DisplayException('You cannot add the owner of a server as a subuser.');
            } elseif (Models\Subuser::select('id')->where('user_id', $user->id)->where('server_id', $server->id)->first()) {
                throw new DisplayException('A subuser with that email already exists for this server.');
            }

            $uuid = new UuidService;
            $subuser = Models\Subuser::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'daemonSecret' => (string) $uuid->generate('servers', 'uuid'),
            ]);

            $perms = Permission::list(true);
            $daemonPermissions = $this->coreDaemonPermissions;

            foreach ($data['permissions'] as $permission) {
                if (array_key_exists($permission, $perms)) {
                    // Build the daemon permissions array for sending.
                    if (! is_null($perms[$permission])) {
                        array_push($daemonPermissions, $perms[$permission]);
                    }

                    Models\Permission::create([
                        'subuser_id' => $subuser->id,
                        'permission' => $permission,
                    ]);
                }
            }

            // Contact Daemon
            // We contact even if they don't have any daemon permissions to overwrite
            // if they did have them previously.

            $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('PATCH', '/server', [
                'json' => [
                    'keys' => [
                        $subuser->daemonSecret => $daemonPermissions,
                    ],
                ],
            ]);

            DB::commit();

            return $subuser;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error attempting to connect to the daemon to add this user.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

        return false;
    }

    /**
     * Revokes a users permissions on a server.
     *
     * @param  int    $id
     * @return void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        $subuser = Models\Subuser::with('server.node')->findOrFail($id);
        $server = $subuser->server;

        DB::beginTransaction();

        try {
            $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('PATCH', '/server', [
                'json' => [
                    'keys' => [
                        $subuser->daemonSecret => [],
                    ],
                ],
            ]);

            foreach ($subuser->permissions as &$permission) {
                $permission->delete();
            }
            $subuser->delete();
            DB::commit();
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error attempting to connect to the daemon to delete this subuser.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Updates permissions for a given subuser.
     *
     * @param  int    $id
     * @param  array  $data
     * @return void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function update($id, array $data)
    {
        $validator = Validator::make($data, [
            'permissions' => 'required|array',
            'user' => 'required|exists:users,id',
            'server' => 'required|exists:servers,id',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->all()));
        }

        $subuser = Models\Subuser::with('server.node')->findOrFail($id);
        $server = $subuser->server;

        DB::beginTransaction();

        try {
            foreach ($subuser->permissions as &$permission) {
                $permission->delete();
            }

            $perms = Permission::list(true);
            $daemonPermissions = $this->coreDaemonPermissions;

            foreach ($data['permissions'] as $permission) {
                if (array_key_exists($permission, $perms)) {
                    // Build the daemon permissions array for sending.
                    if (! is_null($perms[$permission])) {
                        array_push($daemonPermissions, $perms[$permission]);
                    }
                    Models\Permission::create([
                        'subuser_id' => $subuser->id,
                        'permission' => $permission,
                    ]);
                }
            }

            // Contact Daemon
            // We contact even if they don't have any daemon permissions to overwrite
            // if they did have them previously.
            $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('PATCH', '/server', [
                'json' => [
                    'keys' => [
                        $subuser->daemonSecret => $daemonPermissions,
                    ],
                ],
            ]);

            DB::commit();
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error attempting to connect to the daemon to update permissions.', $ex);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
