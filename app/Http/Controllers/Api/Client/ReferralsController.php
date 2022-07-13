<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\ReferralCodeTransformer;
use Pterodacty\Http\Requests\Api\Client\Account\StoreReferralCodeRequest;

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
     * Use a referral code.
     * 
     * @throws DisplayException
     */
    public function use(ClientApiRequest $request): JsonResponse
    {
        $reward = $this->settings->get('jexactyl::referrals:reward', 0);
        $code = $request->input('code');

        // Get the user who owns the referral code.
        $id = DB::table('referral_codes')
            ->where('code', $code)
            ->first();

        $referrer = User::where('id', $id->user_id)->first();

        if ($id->user_id == $request->user()->id) {
            throw new DisplayException('You can\'t use your own referral code.');
        };

        // Update the user with the code and give them the reward.
        $request->user()->update([
            'referral_code' => $code,
            'store_balance' => $request->user()->store_balance + $reward,
        ]);

        // Give the reward to the referrer.
        $referrer->update([
            'store_balance' => $referrer->store_balance + $reward,
        ]);

        // Return a success code.
        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Store a new referral code for a user's account.
     * 
     * @throws DisplayException
     */
    public function store(ClientApiRequest $request): array
    {
        if ($request->user()->referralCodes->count() >= 3) {
            throw new DisplayException('You cannot have more than 3 referral codes.');
        }

        $code = $request->user()->referralCodes()->create([
            'user_id' => $request->user()->id,
            'code' => $this->generate(),
        ]);

        return $this->fractal->item($code)
            ->transformWith($this->getTransformer(ReferralCodeTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a referral code.
     */
    public function delete(ClientApiRequest $request, string $code): JsonResponse
    {
        /** @var \Pterodactyl\Models\ReferralCode $code */
        $referralCode = $request->user()->referralCodes()
            ->where('code', $code)
            ->firstOrFail();
    
        $referralCode->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Returns a string used for creating
     * referral codes for the Panel.
     */
    public function generate(): string
    {
        $chars = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, 16);
    }
}
