<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Services\Acl\Api\AdminAcl;
use App\Http\Controllers\Controller;
use App\Services\Api\KeyCreationService;
use App\Contracts\Repository\ApiKeyRepositoryInterface;
use App\Http\Requests\Admin\Api\StoreApplicationApiKeyRequest;

class ApiController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \App\Services\Api\KeyCreationService
     */
    private $keyCreationService;

    /**
     * @var \App\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * ApplicationApiController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                           $alert
     * @param \App\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \App\Services\Api\KeyCreationService                $keyCreationService
     */
    public function __construct(
        AlertsMessageBag $alert,
        ApiKeyRepositoryInterface $repository,
        KeyCreationService $keyCreationService
    ) {
        $this->alert = $alert;
        $this->keyCreationService = $keyCreationService;
        $this->repository = $repository;
    }

    /**
     * Render view showing all of a user's application API keys.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        return view('admin.api.index', [
            'keys' => $this->repository->getApplicationKeys($request->user()),
        ]);
    }

    /**
     * Render view allowing an admin to create a new application API key.
     *
     * @return \Illuminate\View\View
     * @throws \ReflectionException
     */
    public function create(): View
    {
        $resources = AdminAcl::getResourceList();
        sort($resources);

        return view('admin.api.new', [
            'resources' => $resources,
            'permissions' => [
                'r' => AdminAcl::READ,
                'rw' => AdminAcl::READ | AdminAcl::WRITE,
                'n' => AdminAcl::NONE,
            ],
        ]);
    }

    /**
     * Store the new key and redirect the user back to the application key listing.
     *
     * @param \App\Http\Requests\Admin\Api\StoreApplicationApiKeyRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function store(StoreApplicationApiKeyRequest $request): RedirectResponse
    {
        $this->keyCreationService->setKeyType(ApiKey::TYPE_APPLICATION)->handle([
            'memo' => $request->input('memo'),
            'user_id' => $request->user()->id,
        ], $request->getKeyPermissions());

        $this->alert->success('A new application API key has been generated for your account.')->flash();

        return redirect()->route('admin.api.index');
    }

    /**
     * Delete an application API key from the database.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $identifier
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, string $identifier): Response
    {
        $this->repository->deleteApplicationKey($request->user(), $identifier);

        return response('', 204);
    }
}
