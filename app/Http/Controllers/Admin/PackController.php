<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Pack;
use Prologue\Alerts\AlertsMessageBag;
use App\Http\Controllers\Controller;
use App\Services\Packs\ExportPackService;
use App\Services\Packs\PackUpdateService;
use App\Services\Packs\PackCreationService;
use App\Services\Packs\PackDeletionService;
use App\Http\Requests\Admin\PackFormRequest;
use App\Services\Packs\TemplateUploadService;
use App\Contracts\Repository\NestRepositoryInterface;
use App\Contracts\Repository\PackRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class PackController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \App\Services\Packs\PackCreationService
     */
    protected $creationService;

    /**
     * @var \App\Services\Packs\PackDeletionService
     */
    protected $deletionService;

    /**
     * @var \App\Services\Packs\ExportPackService
     */
    protected $exportService;

    /**
     * @var \App\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Services\Packs\PackUpdateService
     */
    protected $updateService;

    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    protected $serviceRepository;

    /**
     * @var \App\Services\Packs\TemplateUploadService
     */
    protected $templateUploadService;

    /**
     * PackController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                         $alert
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \App\Services\Packs\ExportPackService             $exportService
     * @param \App\Services\Packs\PackCreationService           $creationService
     * @param \App\Services\Packs\PackDeletionService           $deletionService
     * @param \App\Contracts\Repository\PackRepositoryInterface $repository
     * @param \App\Services\Packs\PackUpdateService             $updateService
     * @param \App\Contracts\Repository\NestRepositoryInterface $serviceRepository
     * @param \App\Services\Packs\TemplateUploadService         $templateUploadService
     */
    public function __construct(
        AlertsMessageBag $alert,
        ConfigRepository $config,
        ExportPackService $exportService,
        PackCreationService $creationService,
        PackDeletionService $deletionService,
        PackRepositoryInterface $repository,
        PackUpdateService $updateService,
        NestRepositoryInterface $serviceRepository,
        TemplateUploadService $templateUploadService
    ) {
        $this->alert = $alert;
        $this->config = $config;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->exportService = $exportService;
        $this->repository = $repository;
        $this->updateService = $updateService;
        $this->serviceRepository = $serviceRepository;
        $this->templateUploadService = $templateUploadService;
    }

    /**
     * Display listing of all packs on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.packs.index', [
            'packs' => $this->repository->setSearchTerm($request->input('query'))->paginateWithEggAndServerCount(),
        ]);
    }

    /**
     * Display new pack creation form.
     *
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function create()
    {
        return view('admin.packs.new', [
            'nests' => $this->serviceRepository->getWithEggs(),
        ]);
    }

    /**
     * Display new pack creation modal for use with template upload.
     *
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function newTemplate()
    {
        return view('admin.packs.modal', [
            'nests' => $this->serviceRepository->getWithEggs(),
        ]);
    }

    /**
     * Handle create pack request and route user to location.
     *
     * @param \App\Http\Requests\Admin\PackFormRequest $request
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \App\Exceptions\Service\InvalidFileUploadException
     * @throws \App\Exceptions\Service\Pack\InvalidPackArchiveFormatException
     * @throws \App\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \App\Exceptions\Service\Pack\ZipExtractionException
     */
    public function store(PackFormRequest $request)
    {
        if ($request->filled('from_template')) {
            $pack = $this->templateUploadService->handle($request->input('egg_id'), $request->file('file_upload'));
        } else {
            $pack = $this->creationService->handle($request->normalize(), $request->file('file_upload'));
        }

        $this->alert->success(trans('admin/pack.notices.pack_created'))->flash();

        return redirect()->route('admin.packs.view', $pack->id);
    }

    /**
     * Display pack view template to user.
     *
     * @param \App\Models\Pack $pack
     * @return \Illuminate\View\View
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function view(Pack $pack)
    {
        return view('admin.packs.view', [
            'pack' => $this->repository->loadServerData($pack),
            'nests' => $this->serviceRepository->getWithEggs(),
        ]);
    }

    /**
     * Handle updating or deleting pack information.
     *
     * @param \App\Http\Requests\Admin\PackFormRequest $request
     * @param \App\Models\Pack                         $pack
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function update(PackFormRequest $request, Pack $pack)
    {
        $this->updateService->handle($pack, $request->normalize());
        $this->alert->success(trans('admin/pack.notices.pack_updated'))->flash();

        return redirect()->route('admin.packs.view', $pack->id);
    }

    /**
     * Delete a pack if no servers are attached to it currently.
     *
     * @param \App\Models\Pack $pack
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function destroy(Pack $pack)
    {
        $this->deletionService->handle($pack->id);
        $this->alert->success(trans('admin/pack.notices.pack_deleted', [
            'name' => $pack->name,
        ]))->flash();

        return redirect()->route('admin.packs');
    }

    /**
     * Creates an archive of the pack and downloads it to the browser.
     *
     * @param \App\Models\Pack $pack
     * @param bool|string              $files
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Pack\ZipArchiveCreationException
     */
    public function export(Pack $pack, $files = false)
    {
        $filename = $this->exportService->handle($pack, is_string($files));

        if (is_string($files)) {
            return response()->download($filename, 'pack-' . $pack->name . '.zip')->deleteFileAfterSend(true);
        }

        return response()->download($filename, 'pack-' . $pack->name . '.json', [
            'Content-Type' => 'application/json',
        ])->deleteFileAfterSend(true);
    }
}
