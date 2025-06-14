<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class CarrierAccountToken
 *
 * A resource representing a carrier account that can be used to fund a payment.
 *
 * @package PayPal\Api
 *
 * @property string carrier_account_id
 * @property string external_customer_id
 */
class CarrierAccountToken extends PayPalModel
{
    /**
     * ID of a previously saved carrier account resource.
     *
     * @param string $carrier_account_id
     *
     * @return $this
     */
    public function setCarrierAccountId($carrier_account_id)
    {
        $this->carrier_account_id = $carrier_account_id;
        return $this;
    }

    /**
     * ID of a previously saved carrier account resource.
     *
     * @return string
     */
    public function getCarrierAccountId()
    {
        return $this->carrier_account_id;
    }

    /**
     * The unique identifier of the payer used when saving this carrier account instrument.
     *
     * @param string $external_customer_id
     *
     * @return $this
     */
    public function setExternalCustomerId($external_customer_id)
    {
        $this->external_customer_id = $external_customer_id;
        return $this;
    }

    /**
     * The unique identifier of the payer used when saving this carrier account instrument.
     *
     * @return string
     */
    public function getExternalCustomerId()
    {
        return $this->external_customer_id;
    }

}
