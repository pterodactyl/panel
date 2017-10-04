<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin\Services\Options;

use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Pterodactyl\Services\Services\Exporter\XMLExporterService;

class OptionShareController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Services\Exporter\XMLExporterService
     */
    protected $exporterService;

    /**
     * OptionShareController constructor.
     *
     * @param \Pterodactyl\Services\Services\Exporter\XMLExporterService $exporterService
     */
    public function __construct(XMLExporterService $exporterService)
    {
        $this->exporterService = $exporterService;
    }

    /**
     * @param \Pterodactyl\Models\ServiceOption $option
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function export(ServiceOption $option): Response
    {
        return response($this->exporterService->handle($option->id), 200, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename=' . kebab_case($option->name) . '.xml',
            'Content-Type' => 'application/xml',
        ]);
    }
}
