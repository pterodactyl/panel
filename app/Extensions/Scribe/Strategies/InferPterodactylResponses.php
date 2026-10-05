<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Strategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Camel\Extraction\Response as ExtractedResponse;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use Pterodactyl\Extensions\Scribe\Support\PterodactylDocumentation;
use Pterodactyl\Http\Requests\Api\ApiRequest;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;

class InferPterodactylResponses extends Strategy
{
    /**
     * @param  array<string, ApiValue10>  $settings  settings to be applied to this strategy
     * @return list<ScribeResponse>
     */
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $responses = [];
        $existingStatuses = [];
        foreach ($endpointData->responses as $response) {
            if ($response instanceof ExtractedResponse) {
                $existingStatuses[] = $response->status;
            }
        }

        foreach ($this->standardErrorResponses($endpointData) as $response) {
            if (! in_array($response['status'], $existingStatuses, true)) {
                $responses[] = $response;
                $existingStatuses[] = $response['status'];
            }
        }

        return $responses;
    }

    /**
     * @return list<ScribeResponse>
     */
    private function standardErrorResponses(ExtractedEndpointData $endpointData): array
    {
        $responses = [
            [
                'status' => Response::HTTP_UNAUTHORIZED,
                'content' => PterodactylDocumentation::AUTHENTICATION_ERROR,
                'description' => 'Authentication credentials were missing or invalid.',
            ],
        ];

        if ($this->usesApiRequest($endpointData) || $this->canReturnForbidden($endpointData)) {
            $responses[] = [
                'status' => Response::HTTP_FORBIDDEN,
                'content' => PterodactylDocumentation::FORBIDDEN_ERROR,
                'description' => 'The API key does not have permission to perform this action.',
            ];
        }

        if ($this->hasRouteModelBinding($endpointData) || $endpointData->urlParameters !== []) {
            $responses[] = [
                'status' => Response::HTTP_NOT_FOUND,
                'content' => PterodactylDocumentation::NOT_FOUND_ERROR,
                'description' => 'The requested resource could not be found.',
            ];
        }

        if ($endpointData->bodyParameters !== []) {
            $responses[] = [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'content' => PterodactylDocumentation::VALIDATION_ERROR,
                'description' => 'The submitted data is invalid.',
            ];
        }

        return $responses;
    }

    private function canReturnForbidden(ExtractedEndpointData $endpointData): bool
    {
        if (! $endpointData->method instanceof ReflectionMethod) {
            return false;
        }

        $source = PterodactylDocumentation::methodSource($endpointData->method);

        return str_contains($source, 'AuthorizationException')
            || str_contains($source, 'AccessDeniedHttpException')
            || str_contains($source, 'HTTP_FORBIDDEN')
            || str_contains($source, 'Response::HTTP_FORBIDDEN')
            || str_contains($source, 'abort(403')
            || str_contains($source, '->authorize(')
            || str_contains($source, '->can(');
    }

    private function usesApiRequest(ExtractedEndpointData $endpointData): bool
    {
        if (! $endpointData->method instanceof ReflectionFunctionAbstract) {
            return false;
        }

        foreach ($endpointData->method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && (is_a($type->getName(), ApiRequest::class, true) || is_a($type->getName(), ApplicationApiRequest::class, true))
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasRouteModelBinding(ExtractedEndpointData $endpointData): bool
    {
        if (! $endpointData->method instanceof ReflectionFunctionAbstract) {
            return false;
        }

        foreach ($endpointData->method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin() && is_a($type->getName(), Model::class, true)) {
                return true;
            }
        }

        return false;
    }
}
