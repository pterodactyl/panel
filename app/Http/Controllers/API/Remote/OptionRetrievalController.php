<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\API\Remote;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Services\Services\Options\EggConfigurationService;

class OptionRetrievalController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Services\Options\EggConfigurationService
     */
    protected $configurationFileService;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * OptionUpdateController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface       $repository
     * @param \Pterodactyl\Services\Services\Options\EggConfigurationService $configurationFileService
     */
    public function __construct(
        EggRepositoryInterface $repository,
        EggConfigurationService $configurationFileService
    ) {
        $this->configurationFileService = $configurationFileService;
        $this->repository = $repository;
    }

    /**
     * Return a JSON array of service options and the SHA1 hash of thier configuration file.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $options = $this->repository->getAllWithCopyAttributes();

        $response = [];
        $options->each(function ($option) use (&$response) {
            $response[$option->uuid] = sha1(json_encode($this->configurationFileService->handle($option)));
        });

        return response()->json($response);
    }

    /**
     * Return the configuration file for a single service option for the Daemon.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function download(string $uuid): JsonResponse
    {
        $option = $this->repository->getWithCopyAttributes($uuid, 'uuid');

        return response()->json($this->configurationFileService->handle($option));
    }
}
