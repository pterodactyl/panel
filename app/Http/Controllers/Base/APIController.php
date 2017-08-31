<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>.
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

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Models\APIPermission;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Http\Requests\Base\ApiKeyFormRequest;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class APIController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Api\KeyCreationService
     */
    protected $keyService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    protected $repository;

    /**
     * APIController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                           $alert
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Pterodactyl\Services\Api\KeyCreationService                $keyService
     */
    public function __construct(
        AlertsMessageBag $alert,
        ApiKeyRepositoryInterface $repository,
        KeyCreationService $keyService
    ) {
        $this->alert = $alert;
        $this->keyService = $keyService;
        $this->repository = $repository;
    }

    /**
     * Display base API index page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index(Request $request)
    {
        return view('base.api.index', [
            'keys' => $this->repository->findWhere([['user_id', '=', $request->user()->id]]),
        ]);
    }

    /**
     * Display API key creation page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        return view('base.api.new', [
            'permissions' => [
                'user' => collect(APIPermission::CONST_PERMISSIONS)->pull('_user'),
                'admin' => ! $request->user()->root_admin ?: collect(APIPermission::CONST_PERMISSIONS)->except('_user')->toArray(),
            ],
        ]);
    }

    /**
     * Handle saving new API key.
     *
     * @param \Pterodactyl\Http\Requests\Base\ApiKeyFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ApiKeyFormRequest $request)
    {
        $adminPermissions = [];
        if ($request->user()->root_admin) {
            $adminPermissions = $request->input('admin_permissions') ?? [];
        }

        $secret = $this->keyService->handle([
            'user_id' => $request->user()->id,
            'allowed_ips' => $request->input('allowed_ips'),
            'memo' => $request->input('memo'),
        ], $request->input('permissions', []), $adminPermissions);

        $this->alert->success(trans('base.api.index.keypair_created', ['token' => $secret]))->flash();

        return redirect()->route('account.api');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string                   $key
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function revoke(Request $request, $key)
    {
        $this->repository->deleteWhere([
            ['user_id', '=', $request->user()->id],
            ['public', '=', $key],
        ]);

        return response('', 204);
    }
}
