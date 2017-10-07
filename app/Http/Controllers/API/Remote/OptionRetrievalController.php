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
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Services\Services\Options\OptionConfigurationFileService;

class OptionRetrievalController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Services\Options\OptionConfigurationFileService
     */
    protected $configurationFileService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * OptionUpdateController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface    $repository
     * @param \Pterodactyl\Services\Services\Options\OptionConfigurationFileService $configurationFileService
     */
    public function __construct(
        ServiceOptionRepositoryInterface $repository,
        OptionConfigurationFileService $configurationFileService
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
