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
     * @return $this
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function export(ServiceOption $option)
    {
        return response($this->exporterService->handle($option), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
