<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Http;

interface ClientPermissionsRequest
{
    /** The permission the authenticated user needs on the server for this request. */
    public function permission(): string;
}
