<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        return view('base.billing');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5|max:1000',
            'card_token' => 'required',
        ]);
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $user = $request->user();
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'source'  => $request->card_token
            ]);
            $charge = Charge::create([
                'customer' => $customer->id,
                'amount'   => $request->amount * 100,
                'currency' => 'usd'
            ]);
            $user->stripe_card_brand = $request->card_brand;
            $user->stripe_card_last4 = $request->card_last4;
            $user->stripe_customer_id = $customer->id;
            $user->balance += $request->amount;
            $user->save();
        } catch (\Exception $ex) {}
        return redirect()->back();
    }
}
