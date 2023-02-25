<?php

namespace App\Traits;


use App\Models\User;
use Illuminate\Support\Str;


trait Referral
{
    public function createReferralCode()
    {
        $code = Str::random(8);

        // check if code already exists
        if (User::where('referral_code', $code)->exists()) {
            // if exists, generate another code
            return $this->generateReferralCode();
        }
        return $code;
    }
}
