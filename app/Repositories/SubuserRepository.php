<?php

namespace Pterodactyl\Repositories;

use DB;
use Validator;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

class UserRepository
{

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
        'edit-file' => 's:files:read',
        'save-file' => 's:files:post',
        'create-file' => 's:files:post',
        'download-file' => null,
        'upload-file' => 's:files:upload',
        'delete-file' => 's:files:delete',

        // Subusers
        'list-subusers' => null,
        'view-subuser' => null,
        'edit-subuser' => null,
        'create-subuser' => null,
        'delete-subuser' => null,

        // Management
        'set-connection' => null,
        'view-sftp' => null,
        'reset-sftp' => 's:set-password'
    ];

    public function __construct()
    {
        //
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
            'permissions' => 'required|array'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->all()));
        }

        // @TODO the thing.

    }
