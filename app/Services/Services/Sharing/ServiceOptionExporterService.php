<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Sharing;

use Carbon\Carbon;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;

class ServiceOptionExporterService
{
    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * XMLExporterService constructor.
     *
     * @param \Carbon\Carbon                                                     $carbon
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface $repository
     */
    public function __construct(
        Carbon $carbon,
        ServiceOptionRepositoryInterface $repository
    ) {
        $this->carbon = $carbon;
        $this->repository = $repository;
    }

    /**
     * Return an XML structure to represent this service option.
     *
     * @param int $option
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $option): string
    {
        $option = $this->repository->getWithExportAttributes($option);

        $struct = [
            '_comment' => 'DO NOT EDIT: FILE GENERATED AUTOMATICALLY BY PTERODACTYL PANEL - PTERODACTYL.IO',
            'meta' => [
                'version' => 'PTDL_v1',
            ],
            'exported_at' => $this->carbon->now()->toIso8601String(),
            'name' => $option->name,
            'author' => array_get(explode(':', $option->tag), 0),
            'description' => $option->description,
            'image' => $option->docker_image,
            'config' => [
                'files' => $option->inherit_config_files,
                'startup' => $option->inherit_config_startup,
                'logs' => $option->inherit_config_logs,
                'stop' => $option->inherit_config_stop,
            ],
            'scripts' => [
                'installation' => [
                    'script' => $option->copy_script_install,
                    'container' => $option->copy_script_container,
                    'entrypoint' => $option->copy_script_entry,
                ],
            ],
            'variables' => $option->variables->transform(function ($item) {
                return collect($item->toArray())->except([
                    'id', 'option_id', 'created_at', 'updated_at',
                ])->toArray();
            }),
        ];

        return json_encode($struct, JSON_PRETTY_PRINT);
    }
}
