<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Eggs\Variables;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\Variable\ReservedVariableNameException;

class VariableCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $eggRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface
     */
    protected $variableRepository;

    /**
     * VariableCreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface         $eggRepository
     * @param \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface $variableRepository
     */
    public function __construct(
        EggRepositoryInterface $eggRepository,
        EggVariableRepositoryInterface $variableRepository
    ) {
        $this->eggRepository = $eggRepository;
        $this->variableRepository = $variableRepository;
    }

    /**
     * Create a new variable for a given Egg.
     *
     * @param int   $egg
     * @param array $data
     * @return \Pterodactyl\Models\EggVariable
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function handle(int $egg, array $data): EggVariable
    {
        if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
            throw new ReservedVariableNameException(sprintf(
                'Cannot use the protected name %s for this environment variable.',
                array_get($data, 'env_variable')
            ));
        }

        $options = array_get($data, 'options', []);

        return $this->variableRepository->create(array_merge([
            'egg_id' => $egg,
            'user_viewable' => in_array('user_viewable', $options),
            'user_editable' => in_array('user_editable', $options),
        ], $data));
    }
}
