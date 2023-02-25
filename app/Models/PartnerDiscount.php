<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartnerDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_discount',
        'registered_user_discount',
        'referral_system_commission',
    ];

    public static function getDiscount(int $user_id = null)
    {
        if ($partnerDiscount = PartnerDiscount::where('user_id', $user_id ?? Auth::user()->id)->first()) {
            return $partnerDiscount->partner_discount;
        } elseif ($ref_user = DB::table('user_referrals')->where('registered_user_id', '=', $user_id ?? Auth::user()->id)->first()) {
            if ($partnerDiscount = PartnerDiscount::where('user_id', $ref_user->referral_id)->first()) {
                return $partnerDiscount->registered_user_discount;
            }

            return 0;
        }

        return 0;
    }

    public static function getCommission($user_id)
    {
        if ($partnerDiscount = PartnerDiscount::where('user_id', $user_id)->first()) {
            if ($partnerDiscount->referral_system_commission >= 0) {
                return $partnerDiscount->referral_system_commission >= 0;
            }
        }

        return config('SETTINGS::REFERRAL:PERCENTAGE');
    }
}
