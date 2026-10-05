<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Sharing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Eggs\EggImportVariable;
use Pterodactyl\Services\Eggs\EggParserService;
use Throwable;

final readonly class UpdateEggFromImport implements UpdatesEggsFromImports
{
    public function __construct(private EggParserService $parser) {}

    /** @throws InvalidFileUploadException|Throwable */
    public function update(Egg $egg, UploadedFile $file): Egg
    {
        $parsed = $this->parser->handle($file);

        return DB::transaction(function () use ($egg, $parsed): Egg {
            $egg = $this->parser->fillFromParsed($egg, $parsed);
            $egg->save();

            foreach ($parsed->variables as $variable) {
                EggVariable::unguarded(function () use ($egg, $variable): void {
                    $egg->variables()->updateOrCreate([
                        'env_variable' => $variable->environmentVariable,
                    ], $variable->attributes());
                });
            }

            $imported = array_map(fn (EggImportVariable $variable): string => $variable->environmentVariable, $parsed->variables);
            $egg->variables()->whereNotIn('env_variable', $imported)->delete();

            return $egg->refresh();
        });
    }
}
