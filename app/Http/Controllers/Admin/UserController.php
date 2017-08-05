<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UpdateService;
use Pterodactyl\Services\Users\CreationService;
use Pterodactyl\Services\Users\DeletionService;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Http\Requests\Admin\UserFormRequest;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Users\CreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Users\DeletionService
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
     * @var \Pterodactyl\Services\Users\UpdateService
     */
    protected $updateService;

    /**
     * UserController constructor.
     *
     * @param  \Prologue\Alerts\AlertsMessageBag                         $alert
     * @param \Pterodactyl\Services\Users\CreationService                $creationService
     * @param \Pterodactyl\Services\Users\DeletionService                $deletionService
     * @param \Illuminate\Contracts\Translation\Translator               $translator
     * @param \Pterodactyl\Services\Users\UpdateService                  $updateService
     * @param  \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AlertsMessageBag $alert,
        CreationService $creationService,
        DeletionService $deletionService,
        Translator $translator,
        UpdateService $updateService,
        UserRepositoryInterface $repository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->translator = $translator;
        $this->updateService = $updateService;
    }

    /**
     * Display user index page.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $users = $this->repository->search($request->input('query'))->getAllUsersWithCounts();

        return view('admin.users.index', ['users' => $users]);
    }

    /**
     * Display new user page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.users.new');
    }

    /**
     * Display user view page.
     *
     * @param  \Pterodactyl\Models\User $user
     * @return \Illuminate\View\View
     */
    public function view(User $user)
    {
        return view('admin.users.view', ['user' => $user]);
    }

    /**
     * Delete a user from the system.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Pterodactyl\Models\User $user
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

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Create a user.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
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
     * @param  \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
     * @param  \Pterodactyl\Models\User                         $user
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function update(UserFormRequest $request, User $user)
    {
        $this->updateService->handle($user->id, $request->normalize());
        $this->alert->success($this->translator->trans('admin/user.notices.account_updated'))->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Get a JSON response of users on the system.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function json(Request $request)
    {
        return $this->repository->filterUsersByQuery($request->input('q'));
    }
}
