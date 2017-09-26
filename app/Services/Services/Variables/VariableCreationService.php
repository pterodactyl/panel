<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Variables;

use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Models\ServiceVariable;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException;

class VariableCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $serviceOptionRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface
     */
    protected $serviceVariableRepository;

    public function __construct(
        ServiceOptionRepositoryInterface $serviceOptionRepository,
        ServiceVariableRepositoryInterface $serviceVariableRepository
    ) {
        $this->serviceOptionRepository = $serviceOptionRepository;
        $this->serviceVariableRepository = $serviceVariableRepository;
    }

    /**
     * Create a new variable for a given service option.
     *
     * @param int|\Pterodactyl\Models\ServiceOption $option
     * @param array                                 $data
     * @return \Pterodactyl\Models\ServiceVariable
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException
     */
    public function handle($option, array $data)
    {
        if ($option instanceof ServiceOption) {
            $option = $option->id;
        }

        if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', ServiceVariable::RESERVED_ENV_NAMES))) {
            throw new ReservedVariableNameException(sprintf(
                'Cannot use the protected name %s for this environment variable.',
                array_get($data, 'env_variable')
            ));
        }

        $options = array_get($data, 'options', []);

        return $this->serviceVariableRepository->create(array_merge([
            'option_id' => $option,
            'user_viewable' => in_array('user_viewable', $options),
            'user_editable' => in_array('user_editable', $options),
        ], $data));
    }
}
