<?php

namespace Pterodactyl\Services\Eggs;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Egg;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Exceptions\Service\Egg\NoParentConfigurationFoundException;

// When a mommy and a daddy pterodactyl really like each other...
class EggCreationService
{
    /**
     * EggCreationService constructor.
     */
    public function __construct(private ConfigRepository $config)
    {
    }

    /**
     * Create a new service option and assign it to the given service.
     *
     * @throws NoParentConfigurationFoundException
     */
    public function handle(array $data): Egg
    {
        $data['config_from'] = array_get($data, 'config_from');
        if (!is_null($data['config_from'])) {
            $results = Egg::query()
                ->where('nest_id', array_get($data, 'nest_id'))
                ->where('id', array_get($data, 'config_from'))
                ->count();

            if ($results !== 1) {
                throw new NoParentConfigurationFoundException(trans('exceptions.nest.egg.must_be_child'));
            }
        }

        /** @var Egg $egg */
        $egg = Egg::query()->create(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
            'author' => $this->config->get('pterodactyl.service.author'),
        ]));

        return $egg;
    }
}
