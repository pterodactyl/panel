<?php

namespace Pterodactyl\Extensions\Spatie\Fractalistic;

use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;
use Spatie\Fractal\Fractal as SpatieFractal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\League\Fractal\Serializers\PterodactylSerializer;

class Fractal extends SpatieFractal
{
    /**
     * Create fractal data.
     *
     * @throws \Spatie\Fractalistic\Exceptions\InvalidTransformation
     * @throws \Spatie\Fractalistic\Exceptions\NoTransformerSpecified
     */
    public function createData(): Scope
    {
        // Set the serializer by default.
        if (is_null($this->serializer)) {
            $this->serializer = new PterodactylSerializer();
        }

        // Automatically set the paginator on the response object if the
        // data being provided implements a paginator.
        if (is_null($this->paginator) && $this->data instanceof LengthAwarePaginator) {
            $this->paginator = new IlluminatePaginatorAdapter($this->data);
        }

        // If the resource name is not set attempt to pull it off the transformer
        // itself and set it automatically.
        if (
            is_null($this->resourceName)
            && $this->transformer instanceof TransformerAbstract
            && method_exists($this->transformer, 'getResourceName')
        ) {
            $this->resourceName = $this->transformer->getResourceName();
        }

        return parent::createData();
    }
}
