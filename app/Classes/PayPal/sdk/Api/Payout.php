<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class Payout
 *
 * This object represents a set of payouts that includes status data for the payouts. This object enables you to create a payout using a POST request.
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\PayoutSenderBatchHeader sender_batch_header
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\PayoutItem[] items
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 */
class Payout extends PayPalResourceModel
{
    /**
     * The original batch header as provided by the payment sender.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PayoutSenderBatchHeader $sender_batch_header
     *
     * @return $this
     */
    public function setSenderBatchHeader($sender_batch_header)
    {
        $this->sender_batch_header = $sender_batch_header;
        return $this;
    }

    /**
     * The original batch header as provided by the payment sender.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\PayoutSenderBatchHeader
     */
    public function getSenderBatchHeader()
    {
        return $this->sender_batch_header;
    }

    /**
     * An array of payout items (that is, a set of individual payouts).
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PayoutItem[] $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * An array of payout items (that is, a set of individual payouts).
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\PayoutItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Append Items to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PayoutItem $payoutItem
     * @return $this
     */
    public function addItem($payoutItem)
    {
        if (!$this->getItems()) {
            return $this->setItems(array($payoutItem));
        } else {
            return $this->setItems(
                array_merge($this->getItems(), array($payoutItem))
            );
        }
    }

    /**
     * Remove Items from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PayoutItem $payoutItem
     * @return $this
     */
    public function removeItem($payoutItem)
    {
        return $this->setItems(
            array_diff($this->getItems(), array($payoutItem))
        );
    }

    /**
     * Create a payout batch resource by passing a sender_batch_header and an items array to the request URI. The sender_batch_header contains payout parameters that describe the handling of a batch resource while the items array conatins payout items.
     *
     * @param array $params
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return PayoutBatch
     */
    public function create($params = array(), $apiContext = null, $restCall = null)
    {
        $params = $params ? $params : array();
        ArgumentValidator::validate($params, 'params');
        $payLoad = $this->toJSON();
        $allowedParams = array(
            'sync_mode' => 1,
        );
        $json = self::executeCall(
            "/v1/payments/payouts" . "?" . http_build_query(array_intersect_key($params, $allowedParams)),
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PayoutBatch();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * You can submit a payout with a synchronous API call, which immediately returns the results of a PayPal payment.
     *
     * @param ApiContext $apiContext
     * @param PayPalRestCall $restCall
     * @return PayoutBatch
     */
    public function createSynchronous($apiContext = null, $restCall = null)
    {
        $params = array('sync_mode' => 'true');
        return $this->create($params, $apiContext, $restCall);
    }

    /**
     * Obtain the status of a specific batch resource by passing the payout batch ID to the request URI. You can issue this call multiple times to get the current status.
     *
     * @param string $payoutBatchId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return PayoutBatch
     */
    public static function get($payoutBatchId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($payoutBatchId, 'payoutBatchId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/payouts/$payoutBatchId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PayoutBatch();
        $ret->fromJson($json);
        return $ret;
    }

}
