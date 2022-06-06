<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Store\PurchaseResourceRequest;

class ResourceController extends ClientApiController
{

    /**
     * ResourceController constructor.
     */
    public function __construct(
    )
    {
        parent::__construct();
    }

    /**
     * Allows users to purchase resources via the store.
     * 
     * @throws DisplayException
     */
    public function purchase(PurchaseResourceRequest $request): JsonResponse
    {
        $resource = $request->input('resource');
        $balance = $request->user()->store_balance;
        $cost = $this->getPrice($resource);

        if ($balance < $cost) {
            throw new DisplayException('Unable to purchase resource: You do not have enough credits.');
        };

        $request->user()->update([
            'store_balance' => $balance - $cost,
            'store_'.$resource => /* Doesn't work - $request->user()->store_.$resource */ + $this->getAmount($resource),
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Returns the price of the resource.
     * 
     * @throws DisplayException
     */
    protected function getPrice(string $resource): int
    {
        switch ($resource) {
            case 'cpu':
                return 50;
            case 'memory':
                return 50;
            case 'disk':
                return 50;
            case 'slots':
                return 50;
            case 'ports':
                return 50;
            case 'backups':
                return 50;
            case 'databases':
                return 50;
        }
    }

    /**
     * Returns how much of the resource to assign.
     * 
     * @throws DisplayException
     */
    protected function getAmount(string $resource): int
    {
        switch ($resource) {
            case 'cpu':
                return 50;
            case 'memory':
                return 50;
            case 'disk':
                return 50;
            case 'slots':
                return 50;
            case 'ports':
                return 50;
            case 'backups':
                return 50;
            case 'databases':
                return 50;
        }
    }
}
