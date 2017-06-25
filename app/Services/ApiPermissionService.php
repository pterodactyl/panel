<?php
/*
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

namespace Pterodactyl\Services;

use Pterodactyl\Models\APIPermission;
use Pterodactyl\Exceptions\Model\DataValidationException;

class ApiPermissionService
{
    /**
     * @var \Pterodactyl\Models\APIPermission
     */
    protected $model;

    /**
     * ApiPermissionService constructor.
     *
     * @param \Pterodactyl\Models\APIPermission $model
     */
    public function __construct(APIPermission $model)
    {
        $this->model = $model;
    }

    /**
     * Store a permission key in the database.
     *
     * @param  string  $key
     * @param  string  $permission
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create($key, $permission)
    {
        $instance = $this->model->newInstance([
            'key_id' => $key,
            'permission' => $permission,
        ]);

        if (! $instance->save()) {
            throw new DataValidationException($instance->getValidator());
        }

        return true;
    }

    /**
     * Return all of the permissions available for an API Key.
     *
     * @return array
     */
    public function getPermissions()
    {
        return APIPermission::PERMISSIONS;
    }
}
