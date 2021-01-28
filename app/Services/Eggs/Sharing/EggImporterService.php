<?php

namespace Pterodactyl\Services\Eggs\Sharing;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use Pterodactyl\Models\Egg;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class EggImporterService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface
     */
    protected $eggVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $nestRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggImporterService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        EggRepositoryInterface $repository,
        EggVariableRepositoryInterface $eggVariableRepository,
        NestRepositoryInterface $nestRepository
    ) {
        $this->connection = $connection;
        $this->eggVariableRepository = $eggVariableRepository;
        $this->repository = $repository;
        $this->nestRepository = $nestRepository;
    }

    /**
     * Take an uploaded JSON file and parse it into a new egg.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function handle(UploadedFile $file, int $nest): Egg
    {
        if ($file->getError() !== UPLOAD_ERR_OK || !$file->isFile()) {
            throw new InvalidFileUploadException(sprintf('The selected file ["%s"] was not in a valid format to import. (is_file: %s is_valid: %s err_code: %s err: %s)', $file->getFilename(), $file->isFile() ? 'true' : 'false', $file->isValid() ? 'true' : 'false', $file->getError(), $file->getErrorMessage()));
        }

        /** @var array $parsed */
        $parsed = json_decode($file->openFile()->fread($file->getSize()), true);
        if (json_last_error() !== 0) {
            throw new BadJsonFormatException(trans('exceptions.nest.importer.json_error', ['error' => json_last_error_msg()]));
        }

        if (Arr::get($parsed, 'meta.version') !== 'PTDL_v1') {
            throw new InvalidFileUploadException(trans('exceptions.nest.importer.invalid_json_provided'));
        }

        $nest = $this->nestRepository->getWithEggs($nest);
        $this->connection->beginTransaction();

        /** @var \Pterodactyl\Models\Egg $egg */
        $egg = $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'nest_id' => $nest->id,
            'author' => Arr::get($parsed, 'author'),
            'name' => Arr::get($parsed, 'name'),
            'description' => Arr::get($parsed, 'description'),
            'features' => Arr::get($parsed, 'features'),
            // Maintain backwards compatability for eggs that are still using the old single image
            // string format. New eggs can provide an array of Docker images that can be used.
            'docker_images' => Arr::get($parsed, 'images') ?? [Arr::get($parsed, 'image')],
            'file_denylist' => Collection::make(Arr::get($parsed, 'file_denylist'))->filter(function ($value) {
                return !empty($value);
            }),
            'update_url' => Arr::get($parsed, 'meta.update_url'),
            'config_files' => Arr::get($parsed, 'config.files'),
            'config_startup' => Arr::get($parsed, 'config.startup'),
            'config_logs' => Arr::get($parsed, 'config.logs'),
            'config_stop' => Arr::get($parsed, 'config.stop'),
            'startup' => Arr::get($parsed, 'startup'),
            'script_install' => Arr::get($parsed, 'scripts.installation.script'),
            'script_entry' => Arr::get($parsed, 'scripts.installation.entrypoint'),
            'script_container' => Arr::get($parsed, 'scripts.installation.container'),
            'copy_script_from' => null,
        ], true, true);

        Collection::make($parsed['variables'] ?? [])->each(function (array $variable) use ($egg) {
            $this->eggVariableRepository->create(array_merge($variable, [
                'egg_id' => $egg->id,
            ]));
        });

        $this->connection->commit();

        return $egg;
    }
}
