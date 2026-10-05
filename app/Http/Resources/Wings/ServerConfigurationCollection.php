<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Wings;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

class ServerConfigurationCollection extends ResourceCollection
{
    public function __construct(
        mixed $resource,
        private readonly EggConfigurationService $egg,
        private readonly ServerConfigurationStructureService $configuration,
    ) {
        parent::__construct($resource);
    }

    /**
     * Converts a collection of Server models into an array of configuration responses
     * that can be understood by Wings. Make sure you've properly loaded the required
     * relationships on the Server models before calling this function, otherwise you'll
     * have some serious performance issues from all the N+1 queries.
     *
     * @return array<array-key, array{uuid: string, settings: array<string, JsonValue>, process_configuration: array<string, JsonValue>}>
     */
    public function toArray($request): array
    {
        $result = [];
        foreach ($this->collection ?? [] as $server) {
            throw_unless($server instanceof Server, UnexpectedValueException::class, 'Server configuration resources require Server models.');

            $processConfiguration = $this->egg->handle($server);
            JsonValueGuard::assertPayload9($processConfiguration);

            $result[] = [
                'uuid' => $server->uuid,
                'settings' => $this->configuration->handle($server),
                'process_configuration' => $processConfiguration,
            ];
        }

        return $result;
    }
}
