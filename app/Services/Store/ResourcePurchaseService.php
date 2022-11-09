<?php

namespace Pterodactyl\Services\Store;

use Pterodactyl\Models\User;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Store\PurchaseResourceRequest;

class ResourcePurchaseService
{
    /**
     * ResourcePurchaseService constructor.
     */
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * This service processes the purchase of resources
     * via the Jexactyl Storefront.
     *
     * @throws DisplayException
     */
    public function handle(PurchaseResourceRequest $request)
    {
        $user = $request->user();
        $resource = $request->input('resource');
        $cost = $this->get($resource);
        $current = User::find($user->id)->value('store_' . $resource);

        if ($user->store_balance < $cost) {
            throw new DisplayException('You do not have enough credits.');
        }

        $user->update([
            'store_balance' => $user->store_balance - $cost,
            'store_' . $resource => $current + $this->amount($resource),
        ]);
    }

    /**
     * Returns how much of the resource to assign.
     *
     * @throws DisplayException
     */
    protected function amount(string $resource): int
    {
        return match ($resource) {
            'cpu' => 50,
            'disk', 'memory' => 1024,
            'slots', 'ports', 'backups', 'databases' => 1,
            default => throw new DisplayException('Unable to parse resource type')
        };
    }

    /**
     * Shortcut method to get data from the database.
     */
    protected function get(string $resource): mixed
    {
        if ($resource === 'slots' ||
            $resource === 'ports' ||
            $resource === 'backups' ||
            $resource === 'databases'
        ) {
            $resource = rtrim($resource, 's');
        }

        return $this->settings->get('jexactyl::store:cost:' . $resource);
    }
}
