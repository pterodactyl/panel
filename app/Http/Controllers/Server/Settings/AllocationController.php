<?php

namespace App\Http\Controllers\Server\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\Extensions\HashidsInterface;
use App\Traits\Controllers\JavascriptInjection;
use App\Services\Allocations\SetDefaultAllocationService;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Exceptions\Service\Allocation\AllocationDoesNotBelongToServerException;

class AllocationController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \App\Services\Allocations\SetDefaultAllocationService
     */
    private $defaultAllocationService;

    /**
     * @var \App\Contracts\Extensions\HashidsInterface
     */
    private $hashids;

    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * AllocationController constructor.
     *
     * @param \App\Contracts\Repository\AllocationRepositoryInterface $repository
     * @param \App\Contracts\Extensions\HashidsInterface              $hashids
     * @param \App\Services\Allocations\SetDefaultAllocationService   $defaultAllocationService
     */
    public function __construct(
        AllocationRepositoryInterface $repository,
        HashidsInterface $hashids,
        SetDefaultAllocationService $defaultAllocationService
    ) {
        $this->defaultAllocationService = $defaultAllocationService;
        $this->hashids = $hashids;
        $this->repository = $repository;
    }

    /**
     * Render the allocation management overview page for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('view-allocations', $server);
        $this->setRequest($request)->injectJavascript();

        return view('server.settings.allocation', [
            'allocations' => $this->repository->findWhere([['server_id', '=', $server->id]]),
        ]);
    }

    /**
     * Update the default allocation for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function update(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');
        $this->authorize('edit-allocation', $server);

        $allocation = $this->hashids->decodeFirst($request->input('allocation'), 0);

        try {
            $this->defaultAllocationService->handle($server->id, $allocation);
        } catch (AllocationDoesNotBelongToServerException $exception) {
            return response()->json(['error' => 'No matching allocation was located for this server.'], 404);
        }

        return response()->json();
    }
}
