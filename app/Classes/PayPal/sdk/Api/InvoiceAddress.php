<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

/**
 * Class InvoiceAddress
 *
 * Base Address object used as billing address in a payment or extended for Shipping Address.
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Phone phone
 */
class InvoiceAddress extends BaseAddress
{
    /**
     * Phone number in E.123 format.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Phone $phone
     *
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Phone number in E.123 format.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

}
