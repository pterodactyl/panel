<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\League\Fractal\Serializers;

use Illuminate\Support\Arr;
use League\Fractal\Serializer\ArraySerializer;

class PterodactylSerializer extends ArraySerializer
{
    /**
     * Serialize an item.
     *
     * @param  array<string, mixed>  $data
     * @return array{object: string|null, attributes: array<string, mixed>}
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
     * @param  list<array<string, mixed>>  $data
     * @return array{object: string, data: list<array{object: string|null, attributes: array<string, mixed>}>}
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
     * @param  array<string, mixed>  $transformedData
     * @param  array<string, mixed>  $includedData
     * @return array<string, mixed>
     */
    public function mergeIncludes(array $transformedData, array $includedData): array
    {
        if ($includedData === []) {
            return $transformedData;
        }

        $relationships = Arr::array($transformedData, 'relationships', []);
        foreach ($includedData as $key => $datum) {
            $relationships[$key] = $datum;
        }

        $transformedData['relationships'] = $relationships;

        return $transformedData;
    }
}
