<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Sharing;

use JsonException;
use Pterodactyl\Contracts\Eggs\ImportsCatalogEggs;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Data\EggImportData;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\EggCatalogService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

final readonly class ImportCatalogEgg implements ImportsCatalogEggs
{
    public function __construct(private EggCatalogService $catalog, private ImportsEggs $importer) {}

    public function import(string $id): Egg
    {
        foreach ($this->catalog->all() as $entry) {
            if ($entry->id !== $id) {
                continue;
            }

            $contents = $this->catalog->download($entry);
            try {
                $parsed = EggImportData::fromJson($contents);
            } catch (JsonException|UnexpectedValueException|InvalidFileUploadException $exception) {
                throw new HttpException(502, __('exceptions.egg.catalog.invalid_definition'), $exception);
            }

            return $this->importer->importParsed($parsed);
        }

        throw new NotFoundHttpException(__('exceptions.egg.catalog.egg_missing'));
    }
}
