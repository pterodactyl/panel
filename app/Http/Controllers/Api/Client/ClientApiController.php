<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

abstract class ClientApiController extends ApplicationApiController
{
    use ResolvesRequestContext;
}
