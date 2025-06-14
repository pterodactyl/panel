<?php

namespace Pterodactyl\Classes\PayPal;

use Pterodactyl\Classes\PayPal\sdk\Api\Item;
use Pterodactyl\Classes\PayPal\sdk\Api\Payer;
use Pterodactyl\Classes\PayPal\sdk\Api\Amount;
use Pterodactyl\Classes\PayPal\sdk\Api\Details;
use Pterodactyl\Classes\PayPal\sdk\Api\Payment;
use Pterodactyl\Classes\PayPal\sdk\Api\ItemList;
use Pterodactyl\Classes\PayPal\sdk\Api\Transaction;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Api\RedirectUrls;
use Pterodactyl\Classes\PayPal\sdk\Auth\OAuthTokenCredential;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConnectionException;

class PayPalPayment
{
    /**
     * @var
     */
    public $api, $payer, $details, $amount, $itemList, $transaction, $payment, $redirectUrls, $transactionDescription, $totalPrice, $redirectUrl;

    /**
     * PayPalPayment constructor.
     * @param $paypalApi
     */
    public function __construct($paypalApi)
    {
        $this->api = $paypalApi;

        $this->payer = new Payer();
        $this->details = new Details();
        $this->amount = new Amount();
        $this->itemList = new ItemList();
        $this->transaction = new Transaction();
        $this->payment = new Payment();
        $this->redirectUrls = new RedirectUrls();

        $this->transactionDescription = '';
        $this->totalPrice = 0;
    }

    /**
     * @param $method
     */
    public function setPaymentMethod($method)
    {
        $this->payer->setPaymentMethod($method);
    }

    /**
     * @param $description
     */
    public function setTransactionDescription($description)
    {
        $this->transactionDescription = $description;
    }

    /**
     * @param $name
     * @param $currency
     * @param $quantity
     * @param $price
     */
    public function addItem($name, $currency, $quantity, $price)
    {
        $item = new Item();
        $item->setName($name)
            ->setCurrency($currency)
            ->setQuantity($quantity)
            ->setPrice($price);
        $this->itemList->addItem($item);
        $this->totalPrice = $this->totalPrice += $price;
    }

    /**
     * @param $shippingPrice
     */
    public function setDetails($shippingPrice)
    {
        $this->details->setShipping($shippingPrice)
            ->setSubtotal($this->totalPrice);
        $this->totalPrice = $this->totalPrice += $shippingPrice;
    }

    /**
     * @param $currency
     */
    public function setAmount($currency)
    {
        $this->amount->setCurrency($currency)
            ->setTotal($this->totalPrice)
            ->setDetails($this->details);
    }

    /**
     * @param $returnUrl
     * @param $cancelUrl
     */
    public function setRedirectURLs($returnUrl, $cancelUrl)
    {
        $this->redirectUrls->setReturnUrl($returnUrl)
            ->setCancelUrl($cancelUrl);
    }

    /**
     *
     */
    public function makeTransaction()
    {
        $this->transaction->setAmount($this->amount)
            ->setItemList($this->itemList)
            ->setDescription($this->transactionDescription);
    }

    /**
     *
     */
    public function makePayment()
    {
        $this->payment->setIntent('sale')
            ->setPayer($this->payer)
            ->setTransactions([$this->transaction])
            ->setRedirectUrls($this->redirectUrls);
    }

    /**
     * @return false|string
     */
    public function startPayment()
    {
        try {
            $this->payment->create($this->api);
        } catch (PayPalConnectionException $e) {
            return json_encode(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => $e->getData()
            ));
        }

        foreach ($this->payment->getLinks() as $link) {
            if ($link->getRel() == "approval_url") {
                $redirectUrl = $link->getHref();
            }
        }

        return json_encode(array(
            'status' => 'success',
            'paymentId' => $this->payment->getId(),
            'redirectUrl' => $redirectUrl
        ));
    }

    /**
     * @param $clientId
     * @param $clientSecret
     * @param string $mode
     * @return ApiContext
     */
    public static function getApiContext($clientId, $clientSecret, $mode = 'LIVE')
    {
        $paypal = new ApiContext(
            new OAuthTokenCredential(
                $clientId,
                $clientSecret
            )
        );

        $paypal->setConfig([
            'mode' => $mode,
            'http.ConnectionTimeOut' => 30,
            'log.logEnabled' => false,
            'log.FileName' => '',
            'log.LogLevel' => 'FINE',
            'validation.level' => 'log'
        ]);

        return $paypal;
    }
}
