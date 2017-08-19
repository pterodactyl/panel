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

namespace Pterodactyl\Services\Services;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * ServiceDeletionService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface  $serverRepository
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface $repository
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        ServiceRepositoryInterface $repository
    ) {
        $this->serverRepository = $serverRepository;
        $this->repository = $repository;
    }

    /**
     * Delete a service from the system only if there are no servers attached to it.
     *
     * @param  int $service
     * @return int
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function handle($service)
    {
        $count = $this->serverRepository->findCountWhere([['service_id', '=', $service]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('admin/exceptions.service.delete_has_servers'));
        }

        return $this->repository->delete($service);
    }
}
