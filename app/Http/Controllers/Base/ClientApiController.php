<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Http\Requests\Base\CreateClientApiKeyRequest;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class ClientApiController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Services\Api\KeyCreationService
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * ClientApiController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                           $alert
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Pterodactyl\Services\Api\KeyCreationService                $creationService
     */
    public function __construct(AlertsMessageBag $alert, ApiKeyRepositoryInterface $repository, KeyCreationService $creationService)
    {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->repository = $repository;
    }

    /**
     * Return all of the API keys available to this user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        return view('base.api.index', [
            'keys' => $this->repository->getAccountKeys($request->user()),
        ]);
    }

    /**
     * Render UI to allow creation of an API key.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('base.api.new');
    }

    /**
     * Create the API key and return the user to the key listing page.
     *
     * @param \Pterodactyl\Http\Requests\Base\CreateClientApiKeyRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(CreateClientApiKeyRequest $request): RedirectResponse
    {
        $allowedIps = null;
        if (! is_null($request->input('allowed_ips'))) {
            $allowedIps = json_encode(explode(PHP_EOL, $request->input('allowed_ips')));
        }

        $this->creationService->setKeyType(ApiKey::TYPE_ACCOUNT)->handle([
            'memo' => $request->input('memo'),
            'allowed_ips' => $allowedIps,
            'user_id' => $request->user()->id,
        ]);

        $this->alert->success('A new client API key has been generated for your account.')->flash();

        return redirect()->route('account.api');
    }

    /**
     * Delete a client's API key from the panel.
     *
     * @param \Illuminate\Http\Request $request
     * @param                          $identifier
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $identifier): Response
    {
        $this->repository->deleteAccountKey($request->user(), $identifier);

        return response('', 204);
    }
}
