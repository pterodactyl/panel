<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Shop;

use Carbon\Carbon;
use LaravelDaily\Invoices\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;
use LaravelDaily\Invoices\Classes\Party;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Classes\PayPal\PayPalPayment;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use Pterodactyl\Classes\PayPal\PayPalExecution;
use Pterodactyl\Http\Requests\Api\Client\ShopRequest;
use Pterodactyl\Http\Controllers\Admin\Shop\SettingsController;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settingsRepository;

    /**
     * PaymentController constructor.
     * @param SettingsRepositoryInterface $settingsRepository
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        parent::__construct();

        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @param ShopRequest $request
     * @return array
     */
    public function getDetails(ShopRequest $request)
    {
        $provider = $request->input('provider', '');
        $sessionId = $request->input('sessionId', '');
        $payerId = $request->input('payerId', '');

        $paymentState = '';
        $paymentMessage = '';

        if ($provider == 'paypal') {
            if (empty($sessionId) || empty($payerId)) {
                goto showView;
            }

            $transactions = DB::table('payments')->where('payment_type', '=', 'paypal')->where('session_id', '=', $sessionId)->get();
            if (count($transactions) > 0) {
                goto showView;
            }

            $payment = new PayPalExecution(PayPalPayment::getApiContext(
                $this->settingsRepository->get('settings::shop::paypal::key', ''),
                $this->settingsRepository->get('settings::shop::paypal::secret', ''),
                $this->settingsRepository->get('settings::shop::paypal::mode', 'live')
            ));

            $getPayment = json_decode($payment->getPayment($sessionId), true);
            if ($getPayment['status'] != 'success') {
                $paymentState = 'error';
                $paymentMessage = 'Payment not found.';
                goto showView;
            }

            $payment->setPayerID($payerId);
            $executePayment = json_decode($payment->execute());
            if ($executePayment->status != 'success') {
                $paymentState = 'error';
                $paymentMessage = 'Failed to verify the payment. Please contact us!';
                goto showView;
            }

            DB::table('payments')->insert([
                'payment_type' => 'paypal',
                'user_id' => Auth::user()->id,
                'amount' => $getPayment['items'][0]['amount']['total'],
                'invoice_number' => '',
                'session_id' => $sessionId,
                'completed' => 1,
                'created_at' => Carbon::now(),
            ]);

            DB::table('users')->where('id', '=', Auth::user()->id)->update([
                'credit' => Auth::user()->credit + $getPayment['items'][0]['amount']['total'],
            ]);

            if ($this->settingsRepository->get('settings::shop::invoice::enabled', 0) == 1) {
                $paymentData = DB::table('payments')->where('session_id', '=', $sessionId)->where('payment_type', '=', 'paypal')->get();

                $seller = [
                    'name' => $this->settingsRepository->get('settings::shop::invoice::name', ''),
                    'address' => $this->settingsRepository->get('settings::shop::invoice::address', ''),
                    'phone' => $this->settingsRepository->get('settings::shop::invoice::phone', ''),
                ];

                if (!empty($this->settingsRepository->get('settings::shop::invoice::code', ''))) {
                    $seller['code'] = $this->settingsRepository->get('settings::shop::invoice::code', '');
                }

                if (!empty($this->settingsRepository->get('settings::shop::invoice::vat', ''))) {
                    $seller['vat'] = $this->settingsRepository->get('settings::shop::invoice::vat', '');
                }

                $buyer = [
                    'name' => sprintf('%s %s', Auth::user()->name_first, Auth::user()->name_last),
                    'address' => sprintf('%s, %s, %s', Auth::user()->zip_code, Auth::user()->address, Auth::user()->country),
                    'custom_fields' => [
                        'email' => Auth::user()->email,
                    ],
                ];

                if ($this->settingsRepository->get('settings::shop::invoice::tax', 0) == 0) {
                    $item = (new InvoiceItem())->title('Credit')->pricePerUnit($getPayment['items'][0]['amount']['total']);
                } else {
					$netAmount = $getPayment['items'][0]['amount']['total'] / ($this->settingsRepository->get('settings::shop::invoice::tax', 0) / 100 + 1);
                    $item = (new InvoiceItem())->title('Credit')->pricePerUnit($netAmount);
                }

                Invoice::make()
                    ->seller(new Party($seller))
                    ->buyer(new Party($buyer))
                    ->sequence($paymentData[0]->id)
                    ->currencyCode($this->settingsRepository->get('settings::shop::currency', 'USD'))
                    ->currencySymbol(SettingsController::$currencies[$this->settingsRepository->get('settings::shop::currency', 'USD')])
                    ->taxRate($this->settingsRepository->get('settings::shop::invoice::tax', 0))
                    ->addItem($item)
                    ->payUntilDays(0)
                    ->filename(sprintf('invoice_AA_%s', $paymentData[0]->id))
                    ->save('invoices');

                DB::table('payments')->where('id', '=', $paymentData[0]->id)->update([
                    'invoice_number' => sprintf('invoice_AA_%s.pdf', $paymentData[0]->id),
                ]);
            }

            $paymentState = 'success';
            $paymentMessage = 'You\'ve successfully upload balance to your account.';
        }

        if ($provider == 'stripe') {
            $paymentData = DB::table('payments')->where('payment_type', '=', 'stripe')->where('session_id', '=', $sessionId)->where('completed', '=', 0)->get();
            if (count($paymentData) < 1) {
                $paymentState = 'error';
                $paymentMessage = 'Payment not found.';
                goto showView;
            }

            \Stripe\Stripe::setApiKey($this->settingsRepository->get('settings::shop::stripe::secret', ''));

            try {
                $checkoutSession = \Stripe\Checkout\Session::retrieve($sessionId);
            } catch (ApiErrorException $e) {
                $paymentState = 'error';
                $paymentMessage = 'Payment not found.';
                goto showView;
            }

            try {
                $intent = \Stripe\PaymentIntent::retrieve($checkoutSession->payment_intent);
            } catch (ApiErrorException $e) {
                $paymentState = 'error';
                $paymentMessage = 'Failed to verify the payment. Please contact us!';
                goto showView;
            }

            if ($this->settingsRepository->get('settings::shop::invoice::enabled', 0) == 1) {
                $seller = [
                    'name' => $this->settingsRepository->get('settings::shop::invoice::name', ''),
                    'address' => $this->settingsRepository->get('settings::shop::invoice::address', ''),
                    'phone' => $this->settingsRepository->get('settings::shop::invoice::phone', ''),
                ];

                if (!empty($this->settingsRepository->get('settings::shop::invoice::code', ''))) {
                    $seller['code'] = $this->settingsRepository->get('settings::shop::invoice::code', '');
                }

                if (!empty($this->settingsRepository->get('settings::shop::invoice::vat', ''))) {
                    $seller['vat'] = $this->settingsRepository->get('settings::shop::invoice::vat', '');
                }

                $buyer = [
                    'name' => sprintf('%s %s', Auth::user()->name_first, Auth::user()->name_last),
                    'address' => sprintf('%s, %s, %s', Auth::user()->zip_code, Auth::user()->address, Auth::user()->country),
                    'custom_fields' => [
                        'email' => Auth::user()->email,
                    ],
                ];

                if ($this->settingsRepository->get('settings::shop::invoice::tax', 0) == 0) {
                    $item = (new InvoiceItem())->title('Credit')->pricePerUnit($paymentData[0]->amount);
                } else {
					$netAmount = $paymentData[0]->amount / ($this->settingsRepository->get('settings::shop::invoice::tax', 0) / 100 + 1);
                    $item = (new InvoiceItem())->title('Credit')->pricePerUnit($netAmount);
                }

                Invoice::make()
                    ->seller(new Party($seller))
                    ->buyer(new Party($buyer))
                    ->sequence($paymentData[0]->id)
                    ->currencyCode($this->settingsRepository->get('settings::shop::currency', 'USD'))
                    ->currencySymbol(SettingsController::$currencies[$this->settingsRepository->get('settings::shop::currency', 'USD')])
                    ->taxRate($this->settingsRepository->get('settings::shop::invoice::tax', 0))
                    ->addItem($item)
                    ->payUntilDays(0)
                    ->filename(sprintf('invoice_AA_%s', $paymentData[0]->id))
                    ->save('invoices');

                $invoice_number = sprintf('invoice_AA_%s.pdf', $paymentData[0]->id);
            }

            DB::table('payments')->where('id', '=', $paymentData[0]->id)->update([
                'completed' => 1,
                'invoice_number' => isset($invoice_number) ? $invoice_number : '',
            ]);

            DB::table('users')->where('id', '=', Auth::user()->id)->update([
                'credit' => Auth::user()->credit + $paymentData[0]->amount,
            ]);

            $paymentState = 'success';
            $paymentMessage = 'You\'ve successfully upload balance to your account.';
        }

        showView:

        return [
            'success' => true,
            'data' => [
                'stripeKey' => $this->settingsRepository->get('settings::shop::stripe::key', ''),
                'enabled' => [
                    'paypal' => (int) $this->settingsRepository->get('settings::shop::paypal::enabled', 1),
                    'stripe' => (int) $this->settingsRepository->get('settings::shop::stripe::enabled', 1),
                ],
                'minAmount' => $this->settingsRepository->get('settings::shop::min_amount', 0),
                'maxAmount' => $this->settingsRepository->get('settings::shop::max_amount', 100),
                'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
                'paymentState' => $paymentState,
                'paymentMessage' => $paymentMessage,
                'balance' => Auth::user()->credit,
                'transactions' => DB::table('payments')->where('user_id', '=', Auth::user()->id)->orderBy('created_at', 'DESC')->get(),
            ],
        ];
    }

    /**
     * @param ShopRequest $request
     * @return array
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function paypal(ShopRequest $request)
    {
        $this->validate($request, [
            'amount' => 'required|numeric|min:' . $this->settingsRepository->get('settings::shop::min_amount', 0) . '|max:' . $this->settingsRepository->get('settings::shop::max_amount', 100),
        ]);

        $amount = $request->input('amount', 10);

        if (is_null(Auth::user()->country) || is_null(Auth::user()->address) || is_null(Auth::user()->zip_code)) {
            throw new DisplayException('Please complete your personal details before you upload balance.');
        }

        $payment = new PayPalPayment(PayPalPayment::getApiContext(
            $this->settingsRepository->get('settings::shop::paypal::key', ''),
            $this->settingsRepository->get('settings::shop::paypal::secret', ''),
            $this->settingsRepository->get('settings::shop::paypal::mode', 'live')
        ));
        $payment->setPaymentMethod('paypal');
        $payment->setTransactionDescription('Balance Upload');
        $payment->addItem($amount . ' ' . $this->settingsRepository->get('settings::shop::currency', 'USD') . ' Balance', $this->settingsRepository->get('settings::shop::currency', 'USD'), 1, $amount);
        $payment->setDetails('0');
        $payment->setAmount($this->settingsRepository->get('settings::shop::currency', 'USD'));
        $payment->setRedirectURLs(
            route('index') . '/shop/payments/paypal/success/params',
            route('index') . '/shop/payments/paypal/cancelled'
        );

        $payment->makeTransaction();
        $payment->makePayment();

        $payment = json_decode($payment->startPayment());

        if ($payment->status != 'success') {
            throw new DisplayException('Failed to make the transaction. Please try again later...');
        }

        return [
            'success' => true,
            'data' => [
                'redirectUrl' => $payment->redirectUrl,
            ],
        ];
    }

    /**
     * @param ShopRequest $request
     * @return array
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function stripe(ShopRequest $request)
    {
        $this->validate($request, [
            'amount' => 'required|numeric|min:' . $this->settingsRepository->get('settings::shop::min_amount', 0) . '|max:' . $this->settingsRepository->get('settings::shop::max_amount', 100),
        ]);

        $amount = $request->input('amount', 10);

        if (is_null(Auth::user()->country) || is_null(Auth::user()->address) || is_null(Auth::user()->zip_code)) {
            throw new DisplayException('Please complete your personal details before you upload balance.');
        }

        \Stripe\Stripe::setApiKey($this->settingsRepository->get('settings::shop::stripe::secret', ''));

        try {
            $product = \Stripe\Product::create([
                'name' => $amount . " " . $this->settingsRepository->get('settings::shop::currency', 'USD') . " Balance",
                'description' => 'Balance Upload',
            ]);

            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => $amount * 100,
                'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
            ]);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $price->id,
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => route('index') . '/shop/payments/stripe/success/{CHECKOUT_SESSION_ID}',
                'cancel_url' => route('index') . '/shop/payments/stripe/cancelled',
            ]);
        } catch (ApiErrorException $e) {
            throw new DisplayException('Failed to make the payment.' . $e->getMessage());
        }

        DB::table('payments')->insert([
            'payment_type' => 'stripe',
            'user_id' => Auth::user()->id,
            'amount' => $amount,
            'invoice_number' => '',
            'session_id' => $session['id'],
            'completed' => 0,
            'created_at' => Carbon::now(),
        ]);

        return [
            'success' => true,
            'data' => [
                'sessionId' => $session['id'],
            ],
        ];
    }

    /**
     * @param ShopRequest $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function viewInvoice(ShopRequest $request, $id)
    {
        $payment = DB::table('payments')->where('id', '=', (int) $id)->where('completed', '=', 1)->where('user_id', '=', Auth::user()->id)->get();
        if (count($payment) < 1) {
            throw new NotFoundHttpException('3030');
        }

        return response()->file(storage_path(sprintf('app/invoices/%s', $payment[0]->invoice_number)));
    }
}
