<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\League\Fractal\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class PterodactylSerializer extends ArraySerializer
{
    /**
     * Serialize an item.
     *
     * @param  ApiPayload9  $data
     * @return array{object: string|null, attributes: ApiPayload9}
     */
    public function item(?string $resourceKey, array $data): array
    {
        return [
            'object' => $resourceKey,
            'attributes' => $data,
        ];
    }

    /**
     * Serialize a collection.
     *
     * @param  list<ApiPayload9>  $data
     * @return array{object: string, data: list<array{object: string|null, attributes: ApiPayload9}>}
     */
    public function collection(?string $resourceKey, array $data): array
    {
        $response = [];
        foreach ($data as $datum) {
            $response[] = $this->item($resourceKey, $datum);
        }

        return [
            'object' => 'list',
            'data' => $response,
        ];
    }

    /**
     * Serialize a null resource.
     *
     * @return array{object: string, attributes: null}
     */
    public function null(): ?array
    {
        return [
            'object' => 'null_resource',
            'attributes' => null,
        ];
    }

    /**
     * Merge the included resources with the parent resource being serialized.
     *
     * @param  FractalTransformedPayload  $transformedData
     * @param  ApiPayload9  $includedData
     * @return FractalTransformedPayload
     */
    public function mergeIncludes(array $transformedData, array $includedData): array
    {
        if ($includedData === []) {
            return $transformedData;
        }

        $relationships = $transformedData['relationships'] ?? [];
        foreach ($includedData as $key => $datum) {
            $relationships[$key] = $datum;
        }

        $transformedData['relationships'] = $relationships;

        return $transformedData;
    }
}
