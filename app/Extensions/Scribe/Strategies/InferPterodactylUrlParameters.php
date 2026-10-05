<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Strategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\UrlParameters\GetFromLaravelAPI;
use Pterodactyl\Extensions\Scribe\Support\PterodactylDocumentation;
use Pterodactyl\Support\JsonValueGuard;

class InferPterodactylUrlParameters extends GetFromLaravelAPI
{
    /**
     * @param  array<string, ApiValue10>  $routeRules
     * @return ScribeParameters
     */
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        $inferred = parent::__invoke($endpointData, $routeRules) ?? [];
        $parameters = [];
        foreach ($inferred as $name => $parameter) {
            if (! is_string($name) || ! is_array($parameter)) {
                continue;
            }

            JsonValueGuard::assertPayload($parameter);
            $parameters[$name] = $parameter;
        }

        foreach ($parameters as $name => $parameter) {
            $known = PterodactylDocumentation::urlParameter($name);
            if ($known === null) {
                continue;
            }

            if ($name === 'user' && str_starts_with($endpointData->uri, 'api/client/servers/') && str_contains($endpointData->uri, '/users/{user}')) {
                $known = [
                    'type' => 'string',
                    'description' => 'The user UUID.',
                    'example' => '0d1f6f4d-9f78-4cf9-9d5a-6f7f4c9f4d6a',
                ];
            }

            $parameters[$name] = [
                ...$parameter,
                ...$known,
                'name' => $name,
                'required' => $parameter['required'] ?? true,
            ];
        }

        return $parameters;
    }
}
