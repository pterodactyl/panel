<?php

namespace Pterodactyl\Extensions\Spatie\Fractalistic;

use Illuminate\Database\Eloquent\Model;
use League\Fractal\Serializer\JsonApiSerializer;
use Spatie\Fractalistic\Fractal as SpatieFractal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class Fractal extends SpatieFractal
{
    /**
     * Create fractal data.
     *
     * @return \League\Fractal\Scope
     *
     * @throws \Spatie\Fractalistic\Exceptions\InvalidTransformation
     * @throws \Spatie\Fractalistic\Exceptions\NoTransformerSpecified
     */
    public function createData()
    {
        // Set the serializer by default.
        if (is_null($this->serializer)) {
            $this->serializer = new JsonApiSerializer;
        }

        // Automatically set the paginator on the response object if the
        // data being provided implements a paginator.
        if (is_null($this->paginator) && $this->data instanceof LengthAwarePaginator) {
            $this->paginator = new IlluminatePaginatorAdapter($this->data);
        }

        // Automatically set the resource name if the response data is a model
        // and the resource name is available on the model.
        if (is_null($this->resourceName) && $this->data instanceof Model) {
            if (defined(get_class($this->data) . '::RESOURCE_NAME')) {
                $this->resourceName = constant(get_class($this->data) . '::RESOURCE_NAME');
            }
        }

        if (is_null($this->resourceName) && $this->data instanceof LengthAwarePaginator) {
            $item = collect($this->data->items())->first();
            if ($item instanceof Model) {
                if (defined(get_class($item) . '::RESOURCE_NAME')) {
                    $this->resourceName = constant(get_class($item) . '::RESOURCE_NAME');
                }
            }
        }

        return parent::createData();
    }
}
