<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;

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
     * Update the authenticated user's email address if their password matches.
     *
     * @param UpdateEmailRequest $request
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateEmail(UpdateEmailRequest $request): array
    {
        $updated = $this->updateService->handle($request->user(), [
            'email' => $request->input('email'),
        ]);

        return $this->fractal->item($updated->get('model'))
            ->transformWith($this->getTransformer(AccountTransformer::class))
            ->toArray();
    }
}
