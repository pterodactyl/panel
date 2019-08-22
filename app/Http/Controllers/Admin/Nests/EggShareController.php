<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers\Admin\Nests;

use App\Models\Egg;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Eggs\Sharing\EggExporterService;
use App\Services\Eggs\Sharing\EggImporterService;
use App\Http\Requests\Admin\Egg\EggImportFormRequest;
use App\Services\Eggs\Sharing\EggUpdateImporterService;

class EggShareController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \App\Services\Eggs\Sharing\EggExporterService
     */
    protected $exporterService;

    /**
     * @var \App\Services\Eggs\Sharing\EggImporterService
     */
    protected $importerService;

    /**
     * @var \App\Services\Eggs\Sharing\EggUpdateImporterService
     */
    protected $updateImporterService;

    /**
     * OptionShareController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                           $alert
     * @param \App\Services\Eggs\Sharing\EggExporterService       $exporterService
     * @param \App\Services\Eggs\Sharing\EggImporterService       $importerService
     * @param \App\Services\Eggs\Sharing\EggUpdateImporterService $updateImporterService
     */
    public function __construct(
        AlertsMessageBag $alert,
        EggExporterService $exporterService,
        EggImporterService $importerService,
        EggUpdateImporterService $updateImporterService
    ) {
        $this->alert = $alert;
        $this->exporterService = $exporterService;
        $this->importerService = $importerService;
        $this->updateImporterService = $updateImporterService;
    }

    /**
     * @param \App\Models\Egg $egg
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function export(Egg $egg): Response
    {
        $filename = trim(preg_replace('/[^\w]/', '-', Str::kebab($egg->name)), '-');

        return response($this->exporterService->handle($egg->id), 200, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename=egg-' . $filename . '.json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import a new service option using an XML file.
     *
     * @param \App\Http\Requests\Admin\Egg\EggImportFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \App\Exceptions\Service\InvalidFileUploadException
     */
    public function import(EggImportFormRequest $request): RedirectResponse
    {
        $egg = $this->importerService->handle($request->file('import_file'), $request->input('import_to_nest'));
        $this->alert->success(trans('admin/nests.eggs.notices.imported'))->flash();

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg->id]);
    }

    /**
     * Update an existing Egg using a new imported file.
     *
     * @param \App\Http\Requests\Admin\Egg\EggImportFormRequest $request
     * @param int                                                       $egg
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \App\Exceptions\Service\InvalidFileUploadException
     */
    public function update(EggImportFormRequest $request, int $egg): RedirectResponse
    {
        $this->updateImporterService->handle($egg, $request->file('import_file'));
        $this->alert->success(trans('admin/nests.eggs.notices.updated_via_import'))->flash();

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg]);
    }
}
