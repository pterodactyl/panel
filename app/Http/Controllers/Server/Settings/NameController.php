<?php

namespace App\Http\Controllers\Server\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Traits\Controllers\JavascriptInjection;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Http\Requests\Server\Settings\ChangeServerNameRequest;

class NameController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * NameController constructor.
     *
     * @param \App\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('view-name', $request->attributes->get('server'));
        $this->setRequest($request)->injectJavascript();

        return view('server.settings.name');
    }

    /**
     * Update the stored name for a specific server.
     *
     * @param \App\Http\Requests\Server\Settings\ChangeServerNameRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function update(ChangeServerNameRequest $request): RedirectResponse
    {
        $this->repository->update($request->getServer()->id, $request->validated());

        return redirect()->route('server.settings.name', $request->getServer()->uuidShort);
    }
}
