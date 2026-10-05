<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Pterodactyl\Contracts\Activity\IngestsActivityLogs;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\ActivityEventRequest;

class ActivityProcessingController extends Controller
{
    public function __invoke(ActivityEventRequest $request, IngestsActivityLogs $ingestion): void
    {
        $ingestion->ingest($request->node(), $request->events());
    }
}
