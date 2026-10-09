<?php

namespace Pterodactyl\Extensions\Laravel\Sanctum;

use Pterodactyl\Models\ApiKey;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class NewAccessToken implements Arrayable, Jsonable
{
    /**
     * NewAccessToken constructor.
     */
    public function __construct(public ApiKey $accessToken, public string $plainTextToken)
    {
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, ApiKey|string>
     */
    public function toArray()
    {
        return [
            'accessToken' => $this->accessToken,
            'plainTextToken' => $this->plainTextToken,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
