<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        return view('base.billing');
    }

    public function stripe(Request $request)
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

    private function getPaypalApiContext()
    {
        return new ApiContext(
            new OAuthTokenCredential(
                env('PAYPAL_CLIENT_ID'), 
                env('PAYPAL_CLIENT_SECRET'), 
                env('PAYPAL_CLIENT_ENV')
            )
        );
    }

    public function paypal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5|max:1000',
        ]);
        $apiContext = $this->getPaypalApiContext();
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $amount = new Amount();
        $amount->setTotal($request->amount);
        $amount->setCurrency('USD');
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route('account.billing.paypal.callback'));
        $redirectUrls->setCancelUrl(route('account.billing.paypal.callback'));
        $payment = new Payment();
        $payment->setIntent('sale');
        $payment->setPayer($payer);
        $payment->setTransactions(array($transaction));
        $payment->setRedirectUrls($redirectUrls);
        try {
            $payment->create($apiContext);
            $links = array_filter($payment->links, function($link) {
                return $link->rel == 'approval_url';});
            $link = reset($links)->getHref();
            $meta[$payment->id] = $request->amount;
            session()->put('paypal_meta', $meta);
            return redirect($link);
        } catch (\Exception $ex) {}
        return redirect()->back();
    }

    public function paypalCallback(Request $request) {
        if (!$request->has('paymentId') || !session()->has("paypal_meta.$request->paymentId")) {
            return redirect()->route('account.billing')
                ->withErrors('Something went wrong during the paypal transaction!');
        }
        $user = $request->user();
        $amount = $request->session()->pull("paypal_meta.$request->paymentId");
        $apiContext = $this->getPaypalApiContext();
        $payment = Payment::get($request->paymentId, $apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($request->PayerID);
        try {
            $result = $payment->execute($execution, $apiContext);
            var_dump($result->getState());
            if ($result->getState() == 'approved') {
                $user->balance += $amount;
                $user->save();
            }
        } catch (Exception $ex) {}
        return redirect()->route('account.billing');
    }
}
