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
use Pterodactyl\Services\ApiKeyService;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\ApiKeyRequest;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class APIController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\ApiKeyService
     */
    protected $service;

    /**
     * APIController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                           $alert
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Pterodactyl\Services\ApiKeyService                         $service
     */
    public function __construct(
        AlertsMessageBag $alert,
        ApiKeyRepositoryInterface $repository,
        ApiKeyService $service
    ) {
        $this->alert = $alert;
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Display base API index page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
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
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('base.api.new', [
            'permissions' => [
                'user' => collect(APIPermission::CONST_PERMISSIONS)->pull('_user'),
                'admin' => collect(APIPermission::CONST_PERMISSIONS)->except('_user')->toArray(),
            ],
        ]);
    }

    /**
     * Handle saving new API key.
     *
     * @param \Pterodactyl\Http\Requests\ApiKeyRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ApiKeyRequest $request)
    {
        $adminPermissions = [];
        if ($request->user()->isRootAdmin()) {
            $adminPermissions = $request->input('admin_permissions') ?? [];
        }

        $secret = $this->service->create([
            'user_id' => $request->user()->id,
            'allowed_ips' => $request->input('allowed_ips'),
            'memo' => $request->input('memo'),
        ], $request->input('permissions') ?? [], $adminPermissions);

        $this->alert->success(
            "An API Key-Pair has successfully been generated. The API secret
            for this public key is shown below and will not be shown again.
            <br /><br /><code>{$secret}</code>"
        )->flash();

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
        try {
            $key = $this->repository->withColumns('id')->findFirstWhere([
                ['user_id', '=', $request->user()->id],
                ['public', $key],
            ]);

            $this->service->revoke($key->id);
        } catch (RecordNotFoundException $ex) {
            return abort(404);
        }

        return response('', 204);
    }
}
