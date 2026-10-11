<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;

interface CreatesEggs
{
    /**
     * Create an egg, stamping the configured service author. The input is validated here,
     * so it can come straight from any caller; extension field values go under `extensions`
     * as ValidatedExtensionValues. The Docker images are a newline-separated string, each line
     * an image optionally prefixed with its display name and a pipe. An egg given a
     * config_from copies its config from that egg and needs no config blocks of its own.
     *
     * @param  array<string, mixed>  $input  name, docker_images and startup, and optionally description, force_outgoing_ip, features, file_denylist, config_from, config_stop, config_startup, config_logs and config_files
     */
    public function create(array $input): Egg;
}
