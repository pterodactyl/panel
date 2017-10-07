<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin\Services\Options;

use Illuminate\Http\RedirectResponse;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Pterodactyl\Http\Requests\Admin\Service\OptionImportFormRequest;
use Pterodactyl\Services\Services\Sharing\ServiceOptionExporterService;
use Pterodactyl\Services\Services\Sharing\ServiceOptionImporterService;

class OptionShareController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Services\Sharing\ServiceOptionExporterService
     */
    protected $exporterService;

    /**
     * @var \Pterodactyl\Services\Services\Sharing\ServiceOptionImporterService
     */
    protected $importerService;

    /**
     * OptionShareController constructor.
     *
     * @param \Pterodactyl\Services\Services\Sharing\ServiceOptionExporterService $exporterService
     * @param \Pterodactyl\Services\Services\Sharing\ServiceOptionImporterService $importerService
     */
    public function __construct(
        ServiceOptionExporterService $exporterService,
        ServiceOptionImporterService $importerService
    ) {
        $this->exporterService = $exporterService;
        $this->importerService = $importerService;
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
            'Content-Disposition' => 'attachment; filename=' . kebab_case($option->name) . '.json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import a new service option using an XML file.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\OptionImportFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException
     */
    public function import(OptionImportFormRequest $request): RedirectResponse
    {
        $option = $this->importerService->handle($request->file('import_file'), $request->input('import_to_service'));

        return redirect()->route('admin.services.option.view', ['option' => $option->id]);
    }
}
