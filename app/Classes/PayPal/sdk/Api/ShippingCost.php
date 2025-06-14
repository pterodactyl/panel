<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class ShippingCost
 *
 * Shipping cost in percent or amount.
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency amount
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Tax tax
 */
class ShippingCost extends PayPalModel
{
    /**
     * Shipping cost in amount. Range of 0 to 999999.99.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Shipping cost in amount. Range of 0 to 999999.99.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Tax percentage on shipping amount.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Tax $tax
     *
     * @return $this
     */
    public function setTax($tax)
    {
        $this->tax = $tax;
        return $this;
    }

    /**
     * Tax percentage on shipping amount.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Tax
     */
    public function getTax()
    {
        return $this->tax;
    }

}
