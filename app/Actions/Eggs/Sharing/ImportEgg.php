<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Sharing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Data\EggImportData;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Tag;
use Ramsey\Uuid\Uuid;

final readonly class ImportEgg implements ImportsEggs
{
    public function import(UploadedFile $file): Egg
    {
        return $this->importParsed(EggImportData::fromFile($file));
    }

    public function importParsed(EggImportData $parsed): Egg
    {
        return DB::transaction(function () use ($parsed): Egg {
            $egg = (new Egg)->forceFill([
                'uuid' => Uuid::uuid4()->toString(),
                'author' => $parsed->author,
                'copy_script_from' => null,
                ...$parsed->attributes(),
            ]);
            $egg->save();
            foreach ($parsed->variables as $variable) {
                EggVariable::query()->forceCreate($variable->attributesForEgg($egg->id));
            }

            if ($parsed->tags !== null) {
                $egg->tags()->sync(Tag::findOrCreateFromSlugs($parsed->tags));
            }

            return $egg;
        });
    }
}
