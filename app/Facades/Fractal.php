<?php

declare(strict_types=1);

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal as Fractalistic;

/**
 * A fresh Fractal instance per call, with the request's includes already parsed.
 *
 * @method static Fractalistic item(mixed $data = null, TransformerAbstract|callable|null $transformer = null, string|null $resourceName = null)
 * @method static Fractalistic collection(mixed $data = null, TransformerAbstract|callable|null $transformer = null, string|null $resourceName = null)
 * @method static Fractalistic transformWith(TransformerAbstract|callable $transformer)
 * @method static list<string> requestedIncludes()
 *
 * @see Fractalistic
 */
class Fractal extends Facade
{
    protected static $cached = false;

    protected static function getFacadeAccessor(): string
    {
        return Fractalistic::class;
    }
}
