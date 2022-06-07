<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Store\PurchaseResourceRequest;

class ResourceController extends ClientApiController
{
    private SettingsRepositoryInterface $settings;

    /**
     * ResourceController constructor.
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
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

        // throw new DisplayException('Resource: '.$resource.', Type: '.$type.', Amount: '.$amount);

        $request->user()->update([
            'store_balance' => $balance - $cost,
            'store_'.$resource => $type + $amount,
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
    protected function getResource(PurchaseResourceRequest $request): int
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
