<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\ReferralCodeTransformer;

class ReferralsController extends ClientApiController
{
    /**
     * Returns all of the API keys that exist for the given client.
     *
     * @return array
     */
    public function index(ClientApiRequest $request)
    {
        return $this->fractal->collection($request->user()->referralCodes)
            ->transformWith($this->getTransformer(ReferralCodeTransformer::class))
            ->toArray();
    }

    /**
     * Store a new referral code for a user's account.
     *
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function store(StoreReferralCodeRequest $request)
    {
        if ($request->user()->referralCodes->count() >= 3) {
            throw new DisplayException('You cannot have more than 3 referral codes.');
        }

        $code = $request->user()->referralCodes()->create([
            'user_id' => $request->user()->id,
            'code' => $request->input('code'),
        ]);

        return $this->fractal->item($code->code)
            ->transformWith($this->getTransformer(ReferralCodeTransformer::class))
            ->toArray();
    }
}
