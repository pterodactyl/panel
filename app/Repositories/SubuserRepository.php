<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
use Settings;
use Validator;
use Mail;

use Pterodactyl\Models;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Services\UuidService;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

class SubuserRepository
{

    /**
     * Core permissions required for every subuser on the daemon.
     * Without this we cannot connect the websocket or get basic
     * information about the server.
     * @var array
     */
    protected $coreDaemonPermissions = [
        's:get',
        's:console'
    ];

    /**
     * Allowed permissions and their related daemon permission.
     * @var array
     */
    protected $permissions = [
        // Power Permissions
        'power-start' => 's:power:start',
        'power-stop' => 's:power:stop',
        'power-restart' => 's:power:restart',
        'power-kill' => 's:power:kill',

        // Commands
        'send-command' => 's:command',

        // File Manager
        'list-files' => 's:files:get',
        'edit-files' => 's:files:read',
        'save-files' => 's:files:post',
        'create-files' => 's:files:post',
        'download-files' => null,
        'upload-files' => 's:files:upload',
        'delete-files' => 's:files:delete',

        // Subusers
        'list-subusers' => null,
        'view-subuser' => null,
        'edit-subuser' => null,
        'create-subuser' => null,
        'delete-subuser' => null,

        // Management
        'set-connection' => null,
        'view-startup' => null,
        'edit-startup' => null,
        'view-sftp' => null,
        'reset-sftp' => 's:set-password'
    ];

    public function __construct()
    {
        //
    }

    /**
     * Creates a new subuser on the server.
     * @param  integer $id     The ID of the server to add this subuser to.
     * @param  array  $data
     * @throws DisplayValidationException
     * @throws DisplayException
     * @return integer          Returns the ID of the newly created subuser.
     */
    public function create($sid, array $data)
    {
        $server = Models\Server::findOrFail($sid);
        $validator = Validator::make($data, [
            'permissions' => 'required|array',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->all()));
        }

        DB::beginTransaction();

        // Determine if this user exists or if we need to make them an account.
        $user = Models\User::where('email', $data['email'])->first();
        if (!$user) {
            $password = str_random(16);
            try {
                $repo = new UserRepository;
                $uid = $repo->create($data['email'], $password);
                $user = Models\User::findOrFail($uid);
            } catch (\Exception $ex) {
                throw $ex;
            }
        }

        $uuid = new UuidService;

        $subuser = new Models\Subuser;
        $subuser->fill([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'daemonSecret' => (string) $uuid->generate('servers', 'uuid')
        ]);
        $subuser->save();

        $daemonPermissions = $this->coreDaemonPermissions;
        foreach($data['permissions'] as $permission) {
            if (array_key_exists($permission, $this->permissions)) {
                // Build the daemon permissions array for sending.
                if (!is_null($this->permissions[$permission])) {
                    array_push($daemonPermissions, $this->permissions[$permission]);
                }
                $model = new Models\Permission;
                $model->fill([
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                    'permission' => $permission
                ]);
                $model->save();
            }
        }

        // Contact Daemon
        // We contact even if they don't have any daemon permissions to overwrite
        // if they did have them previously.
        try {

            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $res = $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'keys' => [
                        $subuser->daemonSecret => $daemonPermissions
                    ]
                ]
            ]);

            $email = $data['email'];
            Mail::queue('emails.added-subuser', [
                'serverName' => $server->name,
                'url' => route('server.index', $server->uuidShort),
            ], function ($message) use ($email) {
                $message->to($email);
                $message->from(Settings::get('email_from', env('MAIL_FROM')), Settings::get('email_sender_name', env('MAIL_FROM_NAME', 'Pterodactyl Panel')));
                $message->subject(Settings::get('company') . ' - Added to Server');
            });
            DB::commit();
            return $subuser->id;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error attempting to connect to the daemon to add this user.');
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
        return false;
    }

    /**
     * Revokes a users permissions on a server.
     * @param  integer  $id  The ID of the subuser row in MySQL.
     * @param  array    $data
     * @throws DisplayValidationException
     * @throws DisplayException
     * @return void
     */
    public function delete($id)
    {
        $subuser = Models\Subuser::findOrFail($id);
        $server = Models\Server::findOrFail($subuser->server_id);

        DB::beginTransaction();

        Models\Permission::where('user_id', $subuser->user_id)->where('server_id', $subuser->server_id)->delete();

        try {
            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $res = $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'keys' => [
                        $subuser->daemonSecret => []
                    ]
                ]
            ]);

            $subuser->delete();
            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error attempting to connect to the daemon to delete this subuser.');
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
        return false;
    }

    /**
     * Updates permissions for a given subuser.
     * @param  integer $id  The ID of the subuser row in MySQL. (Not the user ID)
     * @param  array  $data
     * @throws DisplayValidationException
     * @throws DisplayException
     * @return void
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

        $subuser = Models\Subuser::findOrFail($id);
        $server = Models\Server::findOrFail($data['server']);

        DB::beginTransaction();
        Models\Permission::where('user_id', $subuser->user_id)->where('server_id', $subuser->server_id)->delete();

        $daemonPermissions = $this->coreDaemonPermissions;
        foreach($data['permissions'] as $permission) {
            if (array_key_exists($permission, $this->permissions)) {
                // Build the daemon permissions array for sending.
                if (!is_null($this->permissions[$permission])) {
                    array_push($daemonPermissions, $this->permissions[$permission]);
                }
                $model = new Models\Permission;
                $model->fill([
                    'user_id' => $data['user'],
                    'server_id' => $data['server'],
                    'permission' => $permission
                ]);
                $model->save();
            }
        }

        // Contact Daemon
        // We contact even if they don't have any daemon permissions to overwrite
        // if they did have them previously.
        try {
            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $res = $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'keys' => [
                        $subuser->daemonSecret => $daemonPermissions
                    ]
                ]
            ]);

            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('There was an error attempting to connect to the daemon to update permissions.');
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
        return false;
    }

}
