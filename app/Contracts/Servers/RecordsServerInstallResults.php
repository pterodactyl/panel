<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;

interface RecordsServerInstallResults
{
    public function record(Request $request, string $uuid, bool $successful, bool $reinstall): Server;
}
