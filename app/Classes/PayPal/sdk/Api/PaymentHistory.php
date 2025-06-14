<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PaymentHistory
 *
 * List of Payments made by the seller.
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Payment[] payments
 * @property int count
 * @property string next_id
 */
class PaymentHistory extends PayPalModel
{
    /**
     * A list of Payment resources
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Payment[] $payments
     *
     * @return $this
     */
    public function setPayments($payments)
    {
        $this->payments = $payments;
        return $this;
    }

    /**
     * A list of Payment resources
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Payment[]
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * Append Payments to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Payment $payment
     * @return $this
     */
    public function addPayment($payment)
    {
        if (!$this->getPayments()) {
            return $this->setPayments(array($payment));
        } else {
            return $this->setPayments(
                array_merge($this->getPayments(), array($payment))
            );
        }
    }

    /**
     * Remove Payments from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Payment $payment
     * @return $this
     */
    public function removePayment($payment)
    {
        return $this->setPayments(
            array_diff($this->getPayments(), array($payment))
        );
    }

    /**
     * Number of items returned in each range of results. Note that the last results range could have fewer items than the requested number of items.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Number of items returned in each range of results. Note that the last results range could have fewer items than the requested number of items.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Identifier of the next element to get the next range of results.
     *
     * @param string $next_id
     *
     * @return $this
     */
    public function setNextId($next_id)
    {
        $this->next_id = $next_id;
        return $this;
    }

    /**
     * Identifier of the next element to get the next range of results.
     *
     * @return string
     */
    public function getNextId()
    {
        return $this->next_id;
    }

}
