<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Spatie\Fractalistic;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Extensions\League\Fractal\Serializers\PterodactylSerializer;
use Spatie\Fractal\Fractal as SpatieFractal;
use Spatie\Fractalistic\Exceptions\InvalidTransformation;
use Spatie\Fractalistic\Exceptions\NoTransformerSpecified;
use UnexpectedValueException;

class Fractal extends SpatieFractal
{
    /**
     * Serialize a non-null API resource.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidTransformation
     * @throws NoTransformerSpecified
     */
    public function toResponseArray(): array
    {
        $payload = parent::toArray();

        throw_if($payload === null, UnexpectedValueException::class, 'A Fractal API response cannot use a null resource.');

        return $payload;
    }

    /**
     * Create fractal data.
     *
     * @throws InvalidTransformation
     * @throws NoTransformerSpecified
     */
    public function createData(): Scope
    {
        $this->serializer ??= new PterodactylSerializer;

        // Automatically set the paginator on the response object if the
        // data being provided implements a paginator.
        if (($this->paginator) === null && $this->data instanceof LengthAwarePaginator) {
            $this->paginator = new IlluminatePaginatorAdapter($this->data);
        }

        // If the resource name is not set attempt to pull it off the transformer
        // itself and set it automatically.
        if (
            ($this->resourceName) === null
            && $this->transformer instanceof TransformerAbstract
            && method_exists($this->transformer, 'getResourceName')
        ) {
            $this->resourceName = $this->transformer->getResourceName();
        }

        return parent::createData();
    }
}
