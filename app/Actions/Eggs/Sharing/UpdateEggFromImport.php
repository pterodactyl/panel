<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Sharing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Data\EggImportData;
use Pterodactyl\Data\EggImportVariable;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Tag;

final readonly class UpdateEggFromImport implements UpdatesEggsFromImports
{
    public function update(Egg $egg, UploadedFile $file): Egg
    {
        $parsed = EggImportData::fromFile($file);

        return DB::transaction(function () use ($egg, $parsed): Egg {
            $egg->forceFill($parsed->attributes())->save();

            foreach ($parsed->variables as $variable) {
                EggVariable::unguarded(function () use ($egg, $variable): void {
                    $egg->variables()->updateOrCreate([
                        'env_variable' => $variable->environmentVariable,
                    ], $variable->attributes());
                });
            }

            $imported = array_map(fn (EggImportVariable $variable): string => $variable->environmentVariable, $parsed->variables);
            $egg->variables()->whereNotIn('env_variable', $imported)->delete();

            if ($parsed->tags !== null) {
                $egg->tags()->sync(Tag::findOrCreateFromSlugs($parsed->tags));
            }

            return $egg->refresh();
        });
    }
}
