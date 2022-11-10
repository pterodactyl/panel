<?php

namespace Pterodactyl\Services\Referrals;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ReferralCode;
use Pterodactyl\Models\ReferralUses;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class UseReferralService
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * Process to handle a user using a referral code on
     * their account.
     *
     * @throws DisplayException
     */
    public function handle(ClientApiRequest $request): void
    {
        $user = $request->user();
        $code = $request->input('code');
        $reward = $this->settings->get('jexactyl::referrals:reward', 0);

        $id = ReferralCode::where('code', $code)->first()->user_id;
        $referrer = User::where('id', $id)->first();

        if ($id == $user->id) {
            throw new DisplayException('You can\'t use your own referral code.');
        }

        $user->update([
            'referral_code' => $code,
            'store_balance' => $request->user()->store_balance + $reward,
        ]);

        $referrer->update(['store_balance' => $referrer->store_balance + $reward]);

        ReferralUses::create([
            'user_id' => $user->id,
            'code_used' => $code,
            'referrer_id' => $id,
        ]);
    }
}
