<?php

namespace App\Listeners;

use App\Events\PaymentEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\PartnerDiscount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserPayment
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\PaymentEvent  $event
     * @return void
     */
    public function handle(PaymentEvent $event)
    {
        $user = $event->user;
        $shopProduct = $event->shopProduct;

        // only update user if payment is paid
        if ($event->payment->status != "paid") {
            return;
        }

        //update server limit
        if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0 && $user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
            $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
        }

        //update User with bought item
        if ($shopProduct->type == "Credits") {
            $user->increment('credits', $shopProduct->quantity);
        } elseif ($shopProduct->type == "Server slots") {
            $user->increment('server_limit', $shopProduct->quantity);
        }

        //give referral commission always
        if ((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both") && $shopProduct->type == "Credits" && config("SETTINGS::REFERRAL::ALWAYS_GIVE_COMMISSION") == "true") {
            if ($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()) {
                $ref_user = User::findOrFail($ref_user->referral_id);
                $increment = number_format($shopProduct->quantity * (PartnerDiscount::getCommission($ref_user->id)) / 100, 0, "", "");
                $ref_user->increment('credits', $increment);

                //LOGS REFERRALS IN THE ACTIVITY LOG
                activity()
                    ->performedOn($user)
                    ->causedBy($ref_user)
                    ->log('gained ' . $increment . ' ' . config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME") . ' for commission-referral of ' . $user->name . ' (ID:' . $user->id . ')');
            }
        }
        //update role give Referral-reward
        if ($user->role == 'member') {
            $user->update(['role' => 'client']);

            //give referral commission only on first purchase
            if ((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both") && $shopProduct->type == "Credits" && config("SETTINGS::REFERRAL::ALWAYS_GIVE_COMMISSION") == "false") {
                if ($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()) {
                    $ref_user = User::findOrFail($ref_user->referral_id);
                    $increment = number_format($shopProduct->quantity * (PartnerDiscount::getCommission($ref_user->id)) / 100, 0, "", "");
                    $ref_user->increment('credits', $increment);

                    //LOGS REFERRALS IN THE ACTIVITY LOG
                    activity()
                        ->performedOn($user)
                        ->causedBy($ref_user)
                        ->log('gained ' . $increment . ' ' . config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME") . ' for commission-referral of ' . $user->name . ' (ID:' . $user->id . ')');
                }
            }
        }

        // LOGS PAYMENT IN THE ACTIVITY LOG
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('bought ' . $shopProduct->quantity . ' ' . $shopProduct->type . ' for ' . $shopProduct->price . $shopProduct->currency_code);
    }
}
