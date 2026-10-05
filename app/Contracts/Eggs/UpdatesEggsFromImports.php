<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;
use Throwable;

interface UpdatesEggsFromImports
{
    /** @throws InvalidFileUploadException|Throwable */
    public function update(Egg $egg, UploadedFile $file): Egg;
}
