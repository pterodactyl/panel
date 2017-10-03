<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Options;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\ServiceOption;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException;

class OptionCreationService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * CreationService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                            $config
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface $repository
     */
    public function __construct(ConfigRepository $config, ServiceOptionRepositoryInterface $repository)
    {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new service option and assign it to the given service.
     *
     * @param array $data
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException
     */
    public function handle(array $data): ServiceOption
    {
        if (! is_null(array_get($data, 'config_from'))) {
            $results = $this->repository->findCountWhere([
                ['service_id', '=', array_get($data, 'service_id')],
                ['id', '=', array_get($data, 'config_from')],
            ]);

            if ($results !== 1) {
                throw new NoParentConfigurationFoundException(trans('exceptions.service.options.must_be_child'));
            }
        } else {
            $data['config_from'] = null;
        }

        if (count($parts = explode(':', array_get($data, 'tag'))) > 1) {
            $data['tag'] = $this->config->get('pterodactyl.service.author') . ':' . trim(array_pop($parts));
        } else {
            $data['tag'] = $this->config->get('pterodactyl.service.author') . ':' . trim(array_get($data, 'tag'));
        }

        return $this->repository->create(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
        ]), true, true);
    }
}
