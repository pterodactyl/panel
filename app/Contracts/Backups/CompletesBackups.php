<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Illuminate\Validation\ValidationException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Backup;
use Throwable;

interface CompletesBackups
{
    /**
     * Record the outcome Wings reported for a backup and complete or abort its S3 multipart
     * upload. The input is validated here under the field names of the Wings protocol: a
     * successful backup needs its checksum, checksum type and size, and S3 parts left out are
     * listed from S3.
     *
     * @param  array<string, mixed>  $input  successful, checksum, checksum_type, size and optionally parts, each with an etag and part_number
     *
     * @throws DisplayException
     * @throws ValidationException
     * @throws Throwable
     */
    public function complete(Backup $backup, array $input): void;
}
