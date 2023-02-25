<?php

use App\Events\PaymentEvent;
use App\Events\UserUpdateCreditsEvent;
use App\Models\PartnerDiscount;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;



/**
 * @param Request $request
 * @param ShopProduct $shopProduct
 */
function PaypalPay(Request $request)
{
    /** @var User $user */
    $user = Auth::user();
    $shopProduct = ShopProduct::findOrFail($request->shopProduct);
    $discount = PartnerDiscount::getDiscount();

    // create a new payment
    $payment = Payment::create([
        'user_id' => $user->id,
        'payment_id' => null,
        'payment_method' => 'paypal',
        'type' => $shopProduct->type,
        'status' => 'open',
        'amount' => $shopProduct->quantity,
        'price' => $shopProduct->price - ($shopProduct->price * $discount / 100),
        'tax_value' => $shopProduct->getTaxValue(),
        'tax_percent' => $shopProduct->getTaxPercent(),
        'total_price' => $shopProduct->getTotalPrice(),
        'currency_code' => $shopProduct->currency_code,
        'shop_item_product_id' => $shopProduct->id,
    ]);

    $request = new OrdersCreateRequest();
    $request->prefer('return=representation');
    $request->body = [
        "intent" => "CAPTURE",
        "purchase_units" => [
            [
                "reference_id" => uniqid(),
                "description" => $shopProduct->display . ($discount ? (" (" . __('Discount') . " " . $discount . '%)') : ""),
                "amount"       => [
                    "value"         => $shopProduct->getTotalPrice(),
                    'currency_code' => strtoupper($shopProduct->currency_code),
                    'breakdown' => [
                        'item_total' =>
                        [
                            'currency_code' => strtoupper($shopProduct->currency_code),
                            'value' => $shopProduct->getPriceAfterDiscount(),
                        ],
                        'tax_total' =>
                        [
                            'currency_code' => strtoupper($shopProduct->currency_code),
                            'value' => $shopProduct->getTaxValue(),
                        ]
                    ]
                ]
            ]
        ],
        "application_context" => [
            "cancel_url" => route('payment.Cancel'),
            "return_url" => route('payment.PayPalSuccess', ['payment' => $payment->id]),
            'brand_name' =>  config('app.name', 'Controlpanel.GG'),
            'shipping_preference'  => 'NO_SHIPPING'
        ]


    ];

    try {
        // Call API with your client and get a response for your call
        $response = getPayPalClient()->execute($request);

        // check for any errors in the response
        if ($response->statusCode != 201) {
            throw new \Exception($response->statusCode);
        }

        // make sure the link is not empty
        if (empty($response->result->links[1]->href)) {
            throw new \Exception('No redirect link found');
        }

        Redirect::away($response->result->links[1]->href)->send();
        return;
    } catch (HttpException $ex) {
        Log::error('PayPal Payment: ' . $ex->getMessage());
        $payment->delete();

        Redirect::route('store.index')->with('error', __('Payment failed'))->send();
        return;
    }
}
/**
 * @param Request $laravelRequest
 */
function PaypalSuccess(Request $laravelRequest)
{
    $user = Auth::user();
    $user = User::findOrFail($user->id);

    $payment = Payment::findOrFail($laravelRequest->payment);
    $shopProduct = ShopProduct::findOrFail($payment->shop_item_product_id);

    $request = new OrdersCaptureRequest($laravelRequest->input('token'));
    $request->prefer('return=representation');

    try {
        // Call API with your client and get a response for your call
        $response = getPayPalClient()->execute($request);
        if ($response->statusCode == 201 || $response->statusCode == 200) {
            //update payment
            $payment->update([
                'status' => 'paid',
                'payment_id' => $response->result->id,
            ]);

            event(new UserUpdateCreditsEvent($user));
            event(new PaymentEvent($user, $payment, $shopProduct));

            // redirect to the payment success page with success message
            Redirect::route('home')->with('success', 'Payment successful')->send();
        } elseif (env('APP_ENV') == 'local') {
            // If call returns body in response, you can get the deserialized version from the result attribute of the response
            $payment->delete();
            dd($response);
        } else {
            $payment->update([
                'status' => 'cancelled',
                'payment_id' => $response->result->id,
            ]);
            abort(500);
        }
    } catch (HttpException $ex) {
        if (env('APP_ENV') == 'local') {
            echo $ex->statusCode;
            $payment->delete();
            dd($ex->getMessage());
        } else {
            $payment->update([
                'status' => 'cancelled',
                'payment_id' => $response->result->id,
            ]);
            abort(422);
        }
    }
}
/**
 * @return PayPalHttpClient
 */
function getPayPalClient()
{
    $environment = env('APP_ENV') == 'local'
        ? new SandboxEnvironment(getPaypalClientId(), getPaypalClientSecret())
        : new ProductionEnvironment(getPaypalClientId(), getPaypalClientSecret());
    return new PayPalHttpClient($environment);
}
/**
 * @return string
 */
function getPaypalClientId()
{
    return env('APP_ENV') == 'local' ?  config("SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID") : config("SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID");
}
/**
 * @return string
 */
function getPaypalClientSecret()
{
    return env('APP_ENV') == 'local' ? config("SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET") : config("SETTINGS::PAYMENTS:PAYPAL:SECRET");
}
