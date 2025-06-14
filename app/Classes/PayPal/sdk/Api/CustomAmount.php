<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class CustomAmount
 *
 * Custom amount applied on an invoice. If a label is included then the amount cannot be empty.
 *
 * @package PayPal\Api
 *
 * @property string label
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency amount
 */
class CustomAmount extends PayPalModel
{
    /**
     * Custom amount label. 25 characters max.
     *
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Custom amount label. 25 characters max.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Custom amount value. Range of 0 to 999999.99.
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
     * Custom amount value. Range of 0 to 999999.99.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

}
