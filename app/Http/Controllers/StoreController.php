<?php

namespace App\Http\Controllers;

use App\Models\ShopProduct;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    /** Display a listing of the resource. */
    public function index()
    {
        $isPaymentSetup = false;

        if (
            env('APP_ENV') == 'local' ||
            config('SETTINGS::PAYMENTS:PAYPAL:SECRET') && config('SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID') ||
            config('SETTINGS::PAYMENTS:STRIPE:SECRET') && config('SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET') && config('SETTINGS::PAYMENTS:STRIPE:METHODS')
        ) {
            $isPaymentSetup = true;
        }

        //Required Verification for creating an server
        if (config('SETTINGS::USER:FORCE_EMAIL_VERIFICATION', false) === 'true' && ! Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('profile.index')->with('error', __('You are required to verify your email address before you can purchase credits.'));
        }

        //Required Verification for creating an server
        if (config('SETTINGS::USER:FORCE_DISCORD_VERIFICATION', false) === 'true' && ! Auth::user()->discordUser) {
            return redirect()->route('profile.index')->with('error', __('You are required to link your discord account before you can purchase Credits'));
        }

        return view('store.index')->with([
            'products' => ShopProduct::where('disabled', '=', false)->orderBy('type', 'asc')->orderBy('price', 'asc')->get(),
            'isPaymentSetup' => $isPaymentSetup,
        ]);
    }
}
