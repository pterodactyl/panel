<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Activity;

use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\Node;

interface IngestsActivityLogs
{
    /**
     * Record the activity a node reported against the servers it runs. The input is the
     * report Wings sends, validated here under the field names of its protocol, so it can
     * come straight from any caller. Events for servers on other nodes, and events outside
     * the `server:` namespace, are dropped.
     *
     * @param  array<string, mixed>  $input  data: the events, each with server, event, metadata and timestamp, and optionally user and ip
     *
     * @throws ValidationException
     */
    public function ingest(Node $node, array $input): void;
}
