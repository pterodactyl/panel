<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Store\ResourcePurchaseService;
use Pterodactyl\Transformers\Api\Client\Store\UserTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Store\StoreEarnRequest;
use Pterodactyl\Http\Requests\Api\Client\Store\GetStoreUserRequest;
use Pterodactyl\Http\Requests\Api\Client\Store\PurchaseResourceRequest;

class ResourceController extends ClientApiController
{
    /**
     * ResourceController constructor.
     */
    public function __construct(private ResourcePurchaseService $purchaseService)
    {
        parent::__construct();
    }

    /**
     * Get the resources for the authenticated user.
     *
     * This method is used instead of states so that we can retrieve
     * data via API calls, so the page does not need a full refresh
     * in order to retrieve the values.
     *
     * @throws DisplayException
     */
    public function user(GetStoreUserRequest $request)
    {
        return $this->fractal->item($request->user())
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }

    /**
     * Allows a user to earn credits via passive earning.
     *
     * @throws DisplayException
     */
    public function earn(StoreEarnRequest $request)
    {
        $user = $request->user();
        $amount = $this->settings->get('jexactyl::earn:amount', 0);

        if ($this->settings->get('jexactyl::earn:enabled') == 'false') {
            throw new DisplayException('Credit earning is currently disabled.');
        }

        try {
            $user->update(['store_balance' => $user->store_balance + $amount]);
        } catch (DisplayException $ex) {
            throw new DisplayException('Unable to passively earn coins.');
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Allows users to purchase resources via the store.
     *
     * @throws DisplayException
     */
    public function purchase(PurchaseResourceRequest $request): JsonResponse
    {
        $this->purchaseService->handle($request);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
