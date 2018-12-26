<?php

namespace Pterodactyl\Http\Controllers\Server\Settings;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Servers\ServerDeletionService;

class DeleteController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Pterodactyl\Services\Servers\ServerDeletionService
     */
    private $deletionService;


    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * DeleteController constructor.
     *
     * @param \Pterodactyl\Services\Servers\ServerDeletionService         $deletionService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(
        ServerDeletionService $deletionService,
        ServerRepositoryInterface $repository
    ) {        $this->deletionService = $deletionService;
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $server = $request->attributes->get('server');
        if ($server->owner_id != $request->user()->id) {
            return redirect()->back()->withErrors(trans('server.config.delete.errors.owner'));}
        $this->setRequest($request)->injectJavascript();

        return view('server.settings.delete');
    }

    public function delete(Request $request)
    {
        $server = $request->attributes->get('server');
        if (time() - strtotime($server->created_at) < 3600) {
            return redirect()->back()->withErrors(trans('server.config.delete.errors.time'));}
        if ($server->owner_id != $request->user()->id) {
            return redirect()->back()->withErrors(trans('server.config.delete.errors.owner'));}
        try {
            $this->deletionService->handle($server);
        } catch (\Exception $ex) {
            return redirect()->back()->withErrors(trans('server.config.delete.errors.unknown'));
        }
        return redirect()->route('index');
    }
}
