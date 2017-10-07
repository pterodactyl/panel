<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin\Nests;

use Pterodactyl\Models\Egg;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Pterodactyl\Services\Eggs\Sharing\EggExporterService;
use Pterodactyl\Services\Services\Sharing\EggImporterService;
use Pterodactyl\Http\Requests\Admin\Service\OptionImportFormRequest;

class EggShareController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Eggs\Sharing\EggExporterService
     */
    protected $exporterService;

    /**
     * @var \Pterodactyl\Services\Services\Sharing\EggImporterService
     */
    protected $importerService;

    /**
     * OptionShareController constructor.
     *
     * @param \Pterodactyl\Services\Eggs\Sharing\EggExporterService     $exporterService
     * @param \Pterodactyl\Services\Services\Sharing\EggImporterService $importerService
     */
    public function __construct(
        EggExporterService $exporterService,
        EggImporterService $importerService
    ) {
        $this->exporterService = $exporterService;
        $this->importerService = $importerService;
    }

    /**
     * @param \Pterodactyl\Models\Egg $egg
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function export(Egg $egg): Response
    {
        return response($this->exporterService->handle($egg->id), 200, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename=egg-' . kebab_case($egg->name) . '.json',
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
        $egg = $this->importerService->handle($request->file('import_file'), $request->input('import_to_nest'));

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg->id]);
    }
}
