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
     * Record the outcome Wings reported for a backup. The input is validated here, so it can
     * come straight from any caller, under the field names of the Wings protocol. A
     * successful backup needs its checksum, checksum type and size; S3 parts left out are
     * listed from S3 when the multipart upload is completed.
     *
     * @param  array<string, mixed>  $input  successful, checksum, checksum_type, size and optionally parts, each with an etag and part_number
     *
     * @throws DisplayException
     * @throws ValidationException
     * @throws Throwable
     */
    public function complete(Backup $backup, array $input): void;
}
