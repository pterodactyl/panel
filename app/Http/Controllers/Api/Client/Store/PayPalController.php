<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Exception;
use PayPalHttp\IOException;
use Illuminate\Http\Request;
use PayPalHttp\HttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use Pterodactyl\Exceptions\DisplayException;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Store\Gateways\PayPalRequest;

class PayPalController extends ClientApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Constructs the PayPal order request and redirects
     * the user over to PayPal for credits purchase.
     *
     * @throws DisplayException
     */
    public function purchase(PayPalRequest $request): JsonResponse
    {
        if ($this->settings->get('jexactyl::store:paypal:enabled') != 'true') {
            throw new DisplayException('Unable to purchase via PayPal: module not enabled');
        }

        $client = $this->getClient();
        $amount = $request->input('amount');
        $cost = config('gateways.paypal.cost', 1) / 100 * $amount; // Calculate the cost of credits.
        $currency = config('gateways.currency', 'USD');

        // This isn't the best way of doing things,
        // but we'll store a temporary database entry
        // whenever a purchase request is made so we
        // can use the information later on.
        DB::table('paypal')->insert([
            'user_id' => $request->user()->id,
            'amount' => $amount,
        ]);

        $order = new OrdersCreateRequest();
        $order->prefer('return=representation');

        $order->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => uniqid(),
                    'description' => $amount . ' Credits | ' . $this->settings->get('settings::app:name'),
                    'amount' => [
                        'value' => $cost,
                        'currency_code' => strtoupper($currency),
                        'breakdown' => [
                            'item_total' => ['currency_code' => strtoupper($currency), 'value' => $cost],
                        ],
                    ],
                ],
            ],
            'application_context' => [
                'cancel_url' => route('api.client.store.paypal.cancel'),
                'return_url' => route('api.client.store.paypal.success'),
                'brand_name' => $this->settings->get('settings::app:name'),
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ];

        try {
            $response = $client->execute($order);

            return new JsonResponse($response->result->links[1]->href, 200, [], null, true);
        } catch (Exception $ex) {
            throw new DisplayException('Unable to process order.');
        }
    }

    /**
     * Add balance to a user when the purchase is successful.
     *
     * @throws DisplayException
     */
    public function success(Request $request): RedirectResponse
    {
        $client = $this->getClient();
        $id = $request->user()->id;

        try {
            $order = new OrdersCaptureRequest($request->input('token'));
            $order->prefer('return=representation');

            $temp = DB::table('paypal')
                ->where('user_id', $id)
                ->get();

            $res = $client->execute($order);

            if ($res->statusCode == 200 | 201) {
                $request->user()->update([
                    'store_balance' => $request->user()->store_balance + $temp[0]->amount,
                ]);
            }

            DB::table('paypal')->where('user_id', $id)->delete();

            return redirect('/store');
        } catch (DisplayException $ex) {
            throw new DisplayException('Unable to process order.');
        }
    }

    /**
     * Callback for when a payment is cancelled.
     */
    public function cancel(): RedirectResponse
    {
        return redirect()->route('api.client.store.paypal.cancel');
    }

    /**
     * Returns a PayPal client which can be used
     * for processing orders via the API.
     *
     * @throws DisplayException
     */
    protected function getClient(): PayPalHttpClient
    {
        if (env('APP_ENV') == 'production') {
            $environment = new ProductionEnvironment(
                config('gateways.paypal.client_id'),
                config('gateways.paypal.client_secret')
            );
        } else {
            $environment = new SandboxEnvironment(
                config('gateways.paypal.client_id'),
                config('gateways.paypal.client_secret')
            );
        }

        return new PayPalHttpClient($environment);
    }
}
