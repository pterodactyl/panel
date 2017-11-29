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
use Pterodactyl\Traits\Services\HasUserLevels;
use Pterodactyl\Exceptions\DisplayValidationException;
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
    protected $optionVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validator;

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
     * Validate all of the passed data aganist the given service option variables.
     *
     * @param int   $egg
     * @param array $fields
     * @return \Illuminate\Support\Collection
     */
    public function handle(int $egg, array $fields = []): Collection
    {
        $variables = $this->optionVariableRepository->findWhere([['egg_id', '=', $egg]]);

        return $variables->map(function ($item) use ($fields) {
            // Skip doing anything if user is not an admin and
            // variable is not user viewable or editable.
            if (! $this->isUserLevel(User::USER_LEVEL_ADMIN) && (! $item->user_editable || ! $item->user_viewable)) {
                return false;
            }

            $validator = $this->validator->make([
                'variable_value' => array_get($fields, $item->env_variable),
            ], [
                'variable_value' => $item->rules,
            ]);

            if ($validator->fails()) {
                throw new DisplayValidationException(json_encode(
                    collect([
                        'notice' => [
                            trans('admin/server.exceptions.bad_variable', ['name' => $item->name]),
                        ],
                    ])->merge($validator->errors()->toArray())
                ));
            }

            return (object) [
                'id' => $item->id,
                'key' => $item->env_variable,
                'value' => array_get($fields, $item->env_variable),
            ];
        })->filter(function ($item) {
            return is_object($item);
        });
    }
}
