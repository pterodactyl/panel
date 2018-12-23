<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Traits\Services\HasUserLevels;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;

class VariableValidatorService
{
    use HasUserLevels;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface
     */
    private $optionVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    private $serverVariableRepository;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    private $validator;

    /**
     * VariableValidatorService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface    $optionVariableRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $serverRepository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Illuminate\Contracts\Validation\Factory                            $validator
     */
    public function __construct(
        EggVariableRepositoryInterface $optionVariableRepository,
        ServerRepositoryInterface $serverRepository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        ValidationFactory $validator
    ) {
        $this->optionVariableRepository = $optionVariableRepository;
        $this->serverRepository = $serverRepository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validator = $validator;
    }

    /**
     * Validate all of the passed data against the given service option variables.
     *
     * @param int   $egg
     * @param array $fields
     * @return \Illuminate\Support\Collection
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(int $egg, array $fields = []): Collection
    {
        $variables = $this->optionVariableRepository->findWhere([['egg_id', '=', $egg]]);

        $data = $rules = $customAttributes = [];
        foreach ($variables as $variable) {
            // Don't attempt to validate variables if they aren't user editable
            // and we're not running this at an admin level.
            if (! $variable->user_editable && ! $this->isUserLevel(User::USER_LEVEL_ADMIN)) {
                continue;
            }

            $data['environment'][$variable->env_variable] = array_get($fields, $variable->env_variable);
            $rules['environment.' . $variable->env_variable] = $variable->rules;
            $customAttributes['environment.' . $variable->env_variable] = trans('validation.internal.variable_value', ['env' => $variable->name]);
        }

        $validator = $this->validator->make($data, $rules, [], $customAttributes);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $response = $variables->filter(function ($item) {
            // Skip doing anything if user is not an admin and variable is not user viewable or editable.
            if (! $this->isUserLevel(User::USER_LEVEL_ADMIN) && (! $item->user_editable || ! $item->user_viewable)) {
                return false;
            }

            return true;
        })->map(function ($item) use ($fields) {
            return (object) [
                'id' => $item->id,
                'key' => $item->env_variable,
                'value' => array_get($fields, $item->env_variable),
            ];
        })->filter(function ($item) {
            return is_object($item);
        });

        return $response;
    }
}
