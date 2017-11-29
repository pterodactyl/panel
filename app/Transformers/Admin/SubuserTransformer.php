<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Transformers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Subuser;
use League\Fractal\TransformerAbstract;

class SubuserTransformer extends TransformerAbstract
{
    /**
     * The Illuminate Request object if provided.
     *
     * @var \Illuminate\Http\Request|bool
     */
    protected $request;

    /**
     * Setup request object for transformer.
     *
     * @param \Illuminate\Http\Request|bool $request
     */
    public function __construct($request = false)
    {
        if (! $request instanceof Request && $request !== false) {
            throw new DisplayException('Request passed to constructor must be of type Request or false.');
        }

        $this->request = $request;
    }

    /**
     * Return a generic transformed subuser array.
     *
     * @return array
     */
    public function transform(Subuser $subuser)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-view')) {
            return;
        }

        return [
            'id' => $subuser->id,
            'username' => $subuser->user->username,
            'email' => $subuser->user->email,
            '2fa' => (bool) $subuser->user->use_totp,
            'permissions' => $subuser->permissions->pluck('permission'),
            'created_at' => $subuser->created_at,
            'updated_at' => $subuser->updated_at,
        ];
    }
}
