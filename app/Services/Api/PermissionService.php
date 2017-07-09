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

namespace Pterodactyl\Services\Api;

use Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface;

class PermissionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface
     */
    protected $repository;

    /**
     * ApiPermissionService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface $repository
     */
    public function __construct(ApiPermissionRepositoryInterface $repository)
    {
        $this->repository = $repository;
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
        // @todo handle an array of permissions to do a mass assignment?
        return $this->repository->withoutFresh()->create([
            'key_id' => $key,
            'permission' => $permission,
        ]);
    }

    /**
     * Return all of the permissions available for an API Key.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->repository->getModel()::CONST_PERMISSIONS;
    }
}
