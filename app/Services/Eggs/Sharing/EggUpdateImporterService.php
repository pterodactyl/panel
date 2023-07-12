<?php

namespace Pterodactyl\Services\Eggs\Sharing;

use Pterodactyl\Models\Egg;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Pterodactyl\Models\EggVariable;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Eggs\EggParserService;
use Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;

class EggUpdateImporterService
{
    /**
     * EggUpdateImporterService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private EggParserService $eggParserService
    ) {
    }

    /**
     * Update an existing Egg using an uploaded JSON file.
     *
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException|\Throwable
     */
    public function handle(Egg $egg, UploadedFile $file): Egg
    {
        if ($file->getError() !== UPLOAD_ERR_OK || !$file->isFile()) {
            throw new InvalidFileUploadException(sprintf('The selected file ["%s"] was not in a valid format to import. (is_file: %s is_valid: %s err_code: %s err: %s)', $file->getFilename(), $file->isFile() ? 'true' : 'false', $file->isValid() ? 'true' : 'false', $file->getError(), $file->getErrorMessage()));
        }

        $parsed = json_decode($file->openFile()->fread($file->getSize()), true);
        if (json_last_error() !== 0) {
            throw new BadJsonFormatException(trans('exceptions.nest.importer.json_error', ['error' => json_last_error_msg()]));
        }
        $parsed = $this->eggParserService->handle($parsed);

        return $this->connection->transaction(function () use ($egg, $parsed) {
            $egg = $this->eggParserService->fillFromParsed($egg, $parsed);
            $egg->save();

            // Update existing variables or create new ones.
            foreach ($parsed['variables'] ?? [] as $variable) {
                EggVariable::unguarded(function () use ($egg, $variable) {
                    $egg->variables()->updateOrCreate([
                        'env_variable' => $variable['env_variable'],
                    ], Collection::make($variable)->except('egg_id', 'env_variable')->toArray());
                });
            }

            $imported = array_map(fn ($value) => $value['env_variable'], $parsed['variables'] ?? []);

            $egg->variables()->whereNotIn('env_variable', $imported)->delete();

            return $egg->refresh();
        });
    }
}
