<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Helpers\OAuth2Providers;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Http\Requests\Admin\UserFormRequest;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class UserController extends Controller
{
    use AvailableLanguages, OAuth2Providers;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * UserController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                         $alert
     * @param \Pterodactyl\Services\Users\UserCreationService           $creationService
     * @param \Pterodactyl\Services\Users\UserDeletionService           $deletionService
     * @param \Illuminate\Contracts\Translation\Translator              $translator
     * @param \Pterodactyl\Services\Users\UserUpdateService             $updateService
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AlertsMessageBag $alert,
        ConfigRepository $config,
        UserCreationService $creationService,
        UserDeletionService $deletionService,
        Translator $translator,
        UserUpdateService $updateService,
        UserRepositoryInterface $repository
    ) {
        $this->alert = $alert;
        $this->config = $config;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->translator = $translator;
        $this->updateService = $updateService;
    }

    /**
     * Display user index page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $users = $this->repository->setSearchTerm($request->input('query'))->getAllUsersWithCounts();

        return view('admin.users.index', ['users' => $users]);
    }

    /**
     * Display new user page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.users.new', [
            'languages' => $this->getAvailableLanguages(true),
            'enabled_providers' => $this->config->get('oauth2.enabled') ? implode(',', array_keys($this->getEnabledProviderSettings())) : '',
        ]);
    }

    /**
     * Display user view page.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\View\View
     */
    public function view(User $user)
    {
        return view('admin.users.view', [
            'user' => $user,
            'languages' => $this->getAvailableLanguages(true),
            'enabled_providers' => $this->config->get('oauth2.enabled') ? implode(',', array_keys($this->getEnabledProviderSettings())) : '',
        ]);
    }

    /**
     * Delete a user from the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            throw new DisplayException($this->translator->trans('admin/user.exceptions.user_has_servers'));
        }

        $this->deletionService->handle($user);

        return redirect()->route('admin.users');
    }

    /**
     * Create a user.
     *
     * @param \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function store(UserFormRequest $request)
    {
        $user = $this->creationService->handle($request->normalize());
        $this->alert->success($this->translator->trans('admin/user.notices.account_created'))->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Update a user on the system.
     *
     * @param \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
     * @param \Pterodactyl\Models\User                         $user
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UserFormRequest $request, User $user)
    {
        $this->updateService->setUserLevel(User::USER_LEVEL_ADMIN);

        $data = $this->updateService->handle($user, $request->normalize());

        if (! empty($data->get('exceptions'))) {
            foreach ($data->get('exceptions') as $node => $exception) {
                /** @var \GuzzleHttp\Exception\RequestException $exception */
                /** @var \GuzzleHttp\Psr7\Response|null $response */
                $response = method_exists($exception, 'getResponse') ? $exception->getResponse() : null;
                $message = trans('admin/server.exceptions.daemon_exception', [
                    'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
                ]);

                $this->alert->danger(trans('exceptions.users.node_revocation_failed', [
                    'node' => $node,
                    'error' => $message,
                    'link' => route('admin.nodes.view', $node),
                ]))->flash();
            }
        }

        $this->alert->success($this->translator->trans('admin/user.notices.account_updated'))->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Get a JSON response of users on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function json(Request $request)
    {
        return $this->repository->filterUsersByQuery($request->input('q'));
    }
}
