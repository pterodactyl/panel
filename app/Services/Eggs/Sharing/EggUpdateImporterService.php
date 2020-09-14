<?php

namespace Pterodactyl\Services\Eggs\Sharing;

use Pterodactyl\Models\Egg;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\BadEggFormatException;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;

class EggUpdateImporterService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface
     */
    protected $variableRepository;

    /**
     * EggUpdateImporterService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $repository
     * @param \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface $variableRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        EggRepositoryInterface $repository,
        EggVariableRepositoryInterface $variableRepository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->variableRepository = $variableRepository;
    }

    /**
     * Update an existing Egg using an uploaded YAML file.
     *
     * @param \Pterodactyl\Models\Egg $egg
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadEggFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function handle(Egg $egg, UploadedFile $file)
    {
        if ($file->getError() !== UPLOAD_ERR_OK || ! $file->isFile()) {
            throw new InvalidFileUploadException(
                sprintf(
                    'The selected file ["%s"] was not in a valid format to import. (is_file: %s is_valid: %s err_code: %s err: %s)',
                    $file->getFilename(),
                    $file->isFile() ? 'true' : 'false',
                    $file->isValid() ? 'true' : 'false',
                    $file->getError(),
                    $file->getErrorMessage()
                )
            );
        }

        $fileContent = $file->openFile()->fread($file->getSize());

        $parsed = null;
        try {
            $parsed = Yaml::parse($fileContent, Yaml::PARSE_OBJECT_FOR_MAP);
        } catch (YamlParseException $exception) {
            $parsed = json_decode($fileContent);
            if (json_last_error() !== 0) {
                throw new BadEggFormatException(trans('exceptions.nest.importer.parse_error', [
                    'error' => $exception->getMessage(),
                ]), $exception);
            }
        }

        if (object_get($parsed, 'meta.version') === 'PTDL_v1') {
            $parsed['meta']['version'] = 'PTDL_v2';
            $parsed['config']['files'] = Yaml::dump(json_decode($parsed['config']['files'], true), 8, 2);
            $parsed['config']['startup'] = Yaml::dump(json_decode($parsed['config']['startup'], true), 8, 2);
            $parsed['config']['logs'] = Yaml::dump(json_decode($parsed['config']['logs'], true), 8, 2);
        }

        if (object_get($parsed, 'meta.version') !== 'PTDL_v2') {
            throw new InvalidFileUploadException(trans('exceptions.nest.importer.invalid_egg'));
        }

        $this->connection->beginTransaction();
        $this->repository->update($egg->id, [
            'author' => object_get($parsed, 'author'),
            'name' => object_get($parsed, 'name'),
            'description' => object_get($parsed, 'description'),
            'features' => object_get($parsed, 'features'),
            // Maintain backwards compatibility for eggs that are still using the old single image
            // string format. New eggs can provide an array of Docker images that can be used.
            'docker_images' => object_get($parsed, 'images') ?? [object_get($parsed, 'image')],
            'config_files' => object_get($parsed, 'config.files'),
            'config_startup' => object_get($parsed, 'config.startup'),
            'config_logs' => object_get($parsed, 'config.logs'),
            'config_stop' => object_get($parsed, 'config.stop'),
            'startup' => object_get($parsed, 'startup'),
            'script_install' => object_get($parsed, 'scripts.installation.script'),
            'script_entry' => object_get($parsed, 'scripts.installation.entrypoint'),
            'script_container' => object_get($parsed, 'scripts.installation.container'),
        ], true, true);

        // Update Existing Variables
        collect($parsed->variables)->each(function ($variable) use ($egg) {
            $this->variableRepository->withoutFreshModel()->updateOrCreate([
                'egg_id' => $egg->id,
                'env_variable' => $variable->env_variable,
            ], collect($variable)->except(['egg_id', 'env_variable'])->toArray());
        });

        $imported = collect($parsed->variables)->pluck('env_variable')->toArray();
        $existing = $this->variableRepository->setColumns(['id', 'env_variable'])->findWhere([['egg_id', '=', $egg->id]]);

        // Delete variables not present in the import.
        collect($existing)->each(function ($variable) use ($egg, $imported) {
            if (! in_array($variable->env_variable, $imported)) {
                $this->variableRepository->deleteWhere([
                    ['egg_id', '=', $egg->id],
                    ['env_variable', '=', $variable->env_variable],
                ]);
            }
        });

        $this->connection->commit();
    }
}
