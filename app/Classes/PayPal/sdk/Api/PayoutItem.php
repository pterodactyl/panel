<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class PayoutItem
 *
 * Sender-created description of a payout to a single recipient.
 *
 * @package PayPal\Api
 *
 * @property string recipient_type
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency amount
 * @property string note
 * @property string receiver
 * @property string sender_item_id
 */
class PayoutItem extends PayPalResourceModel
{
    /**
     * The type of identification for the payment receiver. If this field is provided, the payout items without a `recipient_type` will use the provided value. If this field is not provided, each payout item must include a value for the `recipient_type`.
     *
     * @param string $recipient_type
     *
     * @return $this
     */
    public function setRecipientType($recipient_type)
    {
        $this->recipient_type = $recipient_type;
        return $this;
    }

    /**
     * The type of identification for the payment receiver. If this field is provided, the payout items without a `recipient_type` will use the provided value. If this field is not provided, each payout item must include a value for the `recipient_type`.
     *
     * @return string
     */
    public function getRecipientType()
    {
        return $this->recipient_type;
    }

    /**
     * The amount of money to pay a receiver.
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
     * The amount of money to pay a receiver.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Note for notifications. The note is provided by the payment sender. This note can be any string. 4000 characters max.
     *
     * @param string $note
     *
     * @return $this
     */
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    /**
     * Note for notifications. The note is provided by the payment sender. This note can be any string. 4000 characters max.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * The receiver of the payment. In a call response, the format of this value corresponds to the `recipient_type` specified in the request. 127 characters max.
     *
     * @param string $receiver
     *
     * @return $this
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

    /**
     * The receiver of the payment. In a call response, the format of this value corresponds to the `recipient_type` specified in the request. 127 characters max.
     *
     * @return string
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * A sender-specific ID number, used in an accounting system for tracking purposes. 30 characters max.
     *
     * @param string $sender_item_id
     *
     * @return $this
     */
    public function setSenderItemId($sender_item_id)
    {
        $this->sender_item_id = $sender_item_id;
        return $this;
    }

    /**
     * A sender-specific ID number, used in an accounting system for tracking purposes. 30 characters max.
     *
     * @return string
     */
    public function getSenderItemId()
    {
        return $this->sender_item_id;
    }

    /**
     * Obtain the status of a payout item by passing the item ID to the request URI.
     *
     * @param string $payoutItemId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return PayoutItemDetails
     */
    public static function get($payoutItemId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($payoutItemId, 'payoutItemId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/payouts-item/$payoutItemId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PayoutItemDetails();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Cancels the unclaimed payment using the items id passed in the request URI. If an unclaimed item is not claimed within 30 days, the funds will be automatically returned to the sender. This call can be used to cancel the unclaimed item prior to the automatic 30-day return.
     *
     * @param string $payoutItemId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return PayoutItemDetails
     */
    public static function cancel($payoutItemId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($payoutItemId, 'payoutItemId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/payouts-item/$payoutItemId/cancel",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PayoutItemDetails();
        $ret->fromJson($json);
        return $ret;
    }

}
