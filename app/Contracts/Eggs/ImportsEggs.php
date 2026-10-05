<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\EggImportData;
use Throwable;

interface ImportsEggs
{
    /** @throws InvalidFileUploadException|Throwable */
    public function import(UploadedFile $file): Egg;

    public function importParsed(EggImportData $parsed): Egg;
}
