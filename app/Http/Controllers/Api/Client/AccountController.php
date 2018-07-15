<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;

class AccountController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    private $updateService;

    /**
     * AccountController constructor.
     *
     * @param \Pterodactyl\Services\Users\UserUpdateService $updateService
     */
    public function __construct(UserUpdateService $updateService)
    {
        parent::__construct();

        $this->updateService = $updateService;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith($this->getTransformer(AccountTransformer::class))
            ->toArray();
    }

    /**
     * Update the authenticated user's email address.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateEmail(UpdateEmailRequest $request): Response
    {
        $this->updateService->handle($request->user(), $request->validated());

        return response('', Response::HTTP_CREATED);
    }

    /**
     * Update the authenticated user's password.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        $this->updateService->handle($request->user(), $request->validated());

        return response('', Response::HTTP_CREATED);
    }
}
