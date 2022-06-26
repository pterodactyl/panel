<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Illuminate\Http\Response;
use Pterodactyl\Facades\Activity;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
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
    public function __construct()
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
            throw new DisplayException('Credit earning is currently disabled');
            return;
        };

        try {
            $user->update([
                'store_balance' => $user->store_balance + $amount,
            ]);
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
        $balance = $request->user()->store_balance;

        $resource = $request->input('resource');
        $cost = $this->getPrice($resource);
        $type = $this->getResource($request);
        $amount = $this->getAmount($resource);

        if ($balance < $cost) {
            throw new DisplayException('Unable to purchase resource: You do not have enough credits.');
        };

        $request->user()->update([
            'store_balance' => $balance - $cost,
            'store_'.$resource => $type + $amount,
        ]);

        Activity::event('user:store.resource-purchase')
            ->property(['resource' => $resource, 'amount' => $amount])
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Returns the price of the resource.
     * 
     * @throws DisplayException
     */
    protected function getPrice(string $resource): int
    {
        $prefix = 'jexactyl::store:cost:';

        switch ($resource) {
            case 'cpu':
                return $this->settings->get($prefix.'cpu');
            case 'memory':
                return $this->settings->get($prefix.'memory');
            case 'disk':
                return $this->settings->get($prefix.'disk');
            case 'slots':
                return $this->settings->get($prefix.'slot');
            case 'ports':
                return $this->settings->get($prefix.'port');
            case 'backups':
                return $this->settings->get($prefix.'backup');
            case 'databases':
                return $this->settings->get($prefix.'database');
            default:
                throw new DisplayException('Unable to get resource price.');
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
                return 1024;
            case 'disk':
                return 1024;
            case 'slots':
                return 1;
            case 'ports':
                return 1;
            case 'backups':
                return 1;
            case 'databases':
                return 1;
            default:
                throw new DisplayException('Unable to get resource details.');
        }
    }

    /**
     * Return the resource type for database entries.
     * 
     * @throws DisplayException
     */
    protected function getResource(PurchaseResourceRequest $request)
    {
        switch ($request->input('resource')) {
            case 'cpu':
                return $request->user()->store_cpu;
            case 'memory':
                return $request->user()->store_memory;
            case 'disk':
                return $request->user()->store_disk;
            case 'slots':
                return $request->user()->store_slots;
            case 'ports':
                return $request->user()->store_ports;
            case 'backups':
                return $request->user()->store_backups;
            case 'databases':
                return $request->user()->store_databases;
        }
    }
}
