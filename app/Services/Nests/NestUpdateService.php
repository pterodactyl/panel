<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Nests;

use Pterodactyl\Contracts\Repository\NestRepositoryInterface;

class NestUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * NestUpdateService constructor.
     */
    public function __construct(NestRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a nest and prevent changing the author once it is set.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $nest, array $data)
    {
        if (!is_null(array_get($data, 'author'))) {
            unset($data['author']);
        }

        $this->repository->withoutFreshModel()->update($nest, $data);
    }
}
