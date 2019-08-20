<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers\Api\Remote;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Eggs\EggConfigurationService;
use App\Contracts\Repository\EggRepositoryInterface;

class EggRetrievalController extends Controller
{
    /**
     * @var \App\Services\Eggs\EggConfigurationService
     */
    protected $configurationFileService;

    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * OptionUpdateController constructor.
     *
     * @param \App\Contracts\Repository\EggRepositoryInterface $repository
     * @param \App\Services\Eggs\EggConfigurationService       $configurationFileService
     */
    public function __construct(
        EggRepositoryInterface $repository,
        EggConfigurationService $configurationFileService
    ) {
        $this->configurationFileService = $configurationFileService;
        $this->repository = $repository;
    }

    /**
     * Return a JSON array of Eggs and the SHA1 hash of their configuration file.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $eggs = $this->repository->getAllWithCopyAttributes();

        $response = [];
        $eggs->each(function ($egg) use (&$response) {
            $response[$egg->uuid] = sha1(json_encode($this->configurationFileService->handle($egg)));
        });

        return response()->json($response);
    }

    /**
     * Return the configuration file for a single Egg for the Daemon.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function download(string $uuid): JsonResponse
    {
        $option = $this->repository->getWithCopyAttributes($uuid, 'uuid');

        return response()->json($this->configurationFileService->handle($option));
    }
}
