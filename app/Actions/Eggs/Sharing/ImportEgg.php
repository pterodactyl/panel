<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Sharing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Eggs\EggImportData;
use Pterodactyl\Services\Eggs\EggParserService;
use Ramsey\Uuid\Uuid;
use Throwable;

final readonly class ImportEgg implements ImportsEggs
{
    public function __construct(private EggParserService $parser) {}

    /** @throws InvalidFileUploadException|Throwable */
    public function import(UploadedFile $file): Egg
    {
        return $this->importParsed($this->parser->handle($file));
    }

    public function importParsed(EggImportData $parsed): Egg
    {
        return DB::transaction(function () use ($parsed): Egg {
            $egg = (new Egg)->forceFill([
                'uuid' => Uuid::uuid4()->toString(),
                'author' => $parsed->author,
                'copy_script_from' => null,
            ]);
            $egg = $this->parser->fillFromParsed($egg, $parsed);
            $egg->save();
            foreach ($parsed->variables as $variable) {
                EggVariable::query()->forceCreate($variable->attributesForEgg($egg->id));
            }

            return $egg;
        });
    }
}
