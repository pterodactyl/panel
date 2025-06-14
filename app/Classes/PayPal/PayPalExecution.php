<?php

namespace Pterodactyl\Classes\PayPal;

use Pterodactyl\Classes\PayPal\sdk\Api\Payment;
use Pterodactyl\Classes\PayPal\sdk\Api\PaymentExecution;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConnectionException;

class PayPalExecution
{
    /**
     * @var
     */
    public $api, $execution, $payment;

    /**
     * PayPalExecution constructor.
     * @param $paypalApi
     */
    public function __construct($paypalApi)
    {
        $this->api = $paypalApi;

        $this->execution = new PaymentExecution();
    }

    /**
     * @param $paymentId
     * @return false|string
     */
    public function getPayment($paymentId)
    {
        try {
            $this->payment = Payment::get($paymentId, $this->api);
        } catch (PayPalConnectionException $e) {
            return json_encode(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => $e->getData(),
            ));
        }

        return json_encode(array(
            'status' => 'success',
            'state' => $this->payment->getState(),
            'payer' => $this->payment->payer->toArray(),
            'items' => $this->payment->toArray()['transactions'],
        ));
    }

    /**
     * @param $payerID
     */
    public function setPayerID($payerID)
    {
        $this->execution->setPayerId($payerID);
    }

    /**
     * @return false|string
     */
    public function execute()
    {
        try {
            $this->payment->execute($this->execution, $this->api);
        } catch (PayPalConnectionException $e) {
            return json_encode(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => $e->getData(),
            ));
        }

        return json_encode(array(
            'status' => 'success',
            'message' => 'Successfully payment',
        ));
    }
}
