<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class RelatedResources
 *
 * Each one representing a financial transaction (Sale, Authorization, Capture, Refund) related to the payment.
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Sale          sale
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Authorization authorization
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Order         order
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Capture       capture
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Refund        refund
 */
class RelatedResources extends PayPalModel
{
    /**
     * Sale transaction
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Sale $sale
     *
     * @return $this
     */
    public function setSale($sale)
    {
        $this->sale = $sale;
        return $this;
    }

    /**
     * Sale transaction
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Sale
     */
    public function getSale()
    {
        return $this->sale;
    }

    /**
     * Authorization transaction
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Authorization $authorization
     *
     * @return $this
     */
    public function setAuthorization($authorization)
    {
        $this->authorization = $authorization;
        return $this;
    }

    /**
     * Authorization transaction
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Authorization
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * Order transaction
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Order $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Order transaction
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Capture transaction
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Capture $capture
     *
     * @return $this
     */
    public function setCapture($capture)
    {
        $this->capture = $capture;
        return $this;
    }

    /**
     * Capture transaction
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Capture
     */
    public function getCapture()
    {
        return $this->capture;
    }

    /**
     * Refund transaction
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Refund $refund
     *
     * @return $this
     */
    public function setRefund($refund)
    {
        $this->refund = $refund;
        return $this;
    }

    /**
     * Refund transaction
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Refund
     */
    public function getRefund()
    {
        return $this->refund;
    }

}
