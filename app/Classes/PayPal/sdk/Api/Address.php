<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

/**
 * Class Address
 *
 * Base Address object used as billing address in a payment or extended for Shipping Address.
 *
 * @package PayPal\Api
 *
 * @property string phone
 */
class Address extends BaseAddress
{
    /**
     * Phone number in E.123 format.
     *
     * @param string $phone
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
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

}
