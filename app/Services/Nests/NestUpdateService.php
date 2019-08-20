<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Nests;

use Illuminate\Support\Arr;
use App\Contracts\Repository\NestRepositoryInterface;

class NestUpdateService
{
    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * NestUpdateService constructor.
     *
     * @param \App\Contracts\Repository\NestRepositoryInterface $repository
     */
    public function __construct(NestRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a nest and prevent changing the author once it is set.
     *
     * @param int   $nest
     * @param array $data
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $nest, array $data)
    {
        if (! is_null(Arr::get($data, 'author'))) {
            unset($data['author']);
        }

        $this->repository->withoutFreshModel()->update($nest, $data);
    }
}
