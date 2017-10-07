<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Sharing;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\ServiceOption;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\DuplicateOptionTagException;

class ServiceOptionImporterService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $serviceRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface
     */
    protected $serviceVariableRepository;

    /**
     * XMLImporterService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                             $connection
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface         $serviceRepository
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface   $repository
     * @param \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface $serviceVariableRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        ServiceRepositoryInterface $serviceRepository,
        ServiceOptionRepositoryInterface $repository,
        ServiceVariableRepositoryInterface $serviceVariableRepository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->serviceRepository = $serviceRepository;
        $this->serviceVariableRepository = $serviceVariableRepository;
    }

    /**
     * Take an uploaded XML file and parse it into a new service option.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int                           $service
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException
     */
    public function handle(UploadedFile $file, int $service): ServiceOption
    {
        if (! $file->isValid() || ! $file->isFile()) {
            throw new InvalidFileUploadException(trans('exceptions.service.exporter.import_file_error'));
        }

        $parsed = json_decode($file->openFile()->fread($file->getSize()));

        if (object_get($parsed, 'meta.version') !== 'PTDL_v1') {
            throw new InvalidFileUploadException(trans('exceptions.service.exporter.invalid_json_provided'));
        }

        $service = $this->serviceRepository->getWithOptions($service);
        $service->options->each(function ($option) use ($parsed) {
            if ($option->tag === object_get($parsed, 'tag')) {
                throw new DuplicateOptionTagException(trans('exceptions.service.options.duplicate_tag'));
            }
        });

        $this->connection->beginTransaction();
        $option = $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'service_id' => $service->id,
            'name' => object_get($parsed, 'name'),
            'description' => object_get($parsed, 'description'),
            'tag' => object_get($parsed, 'tag'),
            'docker_image' => object_get($parsed, 'image'),
            'config_files' => object_get($parsed, 'config.files'),
            'config_startup' => object_get($parsed, 'config.startup'),
            'config_logs' => object_get($parsed, 'config.logs'),
            'config_stop' => object_get($parsed, 'config.stop'),
            'startup' => object_get($parsed, 'startup'),
            'script_install' => object_get($parsed, 'scripts.installation.script'),
            'script_entry' => object_get($parsed, 'scripts.installation.entrypoint'),
            'script_container' => object_get($parsed, 'scripts.installation.container'),
            'copy_script_from' => null,
        ], true, true);

        collect($parsed->variables)->each(function ($variable) use ($option) {
            $this->serviceVariableRepository->create(array_merge((array) $variable, [
                'option_id' => $option->id,
            ]));
        });

        $this->connection->commit();

        return $option;
    }
}
