<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class Authorization
 *
 * An authorization transaction.
 *
 * @package PayPal\Api
 *
 * @property string                 id
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Amount     amount
 * @property string                 payment_mode
 * @property string                 state
 * @property string                 reason_code
 * @property string                 pending_reason
 * @property string                 protection_eligibility
 * @property string                 protection_eligibility_type
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\FmfDetails fmf_details
 * @property string                 parent_payment
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\ProcessorResponse processor_response
 * @property string                 valid_until
 * @property string                 create_time
 * @property string                 update_time
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[]    links
 */
class Authorization extends PayPalResourceModel
{
    /**
     * ID of the authorization transaction.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * ID of the authorization transaction.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Amount being authorized.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Amount $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Amount being authorized.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Specifies the payment mode of the transaction.
     * Valid Values: ["INSTANT_TRANSFER"]
     *
     * @param string $payment_mode
     *
     * @return $this
     */
    public function setPaymentMode($payment_mode)
    {
        $this->payment_mode = $payment_mode;
        return $this;
    }

    /**
     * Specifies the payment mode of the transaction.
     *
     * @return string
     */
    public function getPaymentMode()
    {
        return $this->payment_mode;
    }

    /**
     * State of the authorization.
     * Valid Values: ["pending", "authorized", "partially_captured", "captured", "expired", "voided"]
     *
     * @param string $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * State of the authorization.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Reason code, `AUTHORIZATION`, for a transaction state of `pending`.
     * Valid Values: ["AUTHORIZATION"]
     *
     * @param string $reason_code
     *
     * @return $this
     */
    public function setReasonCode($reason_code)
    {
        $this->reason_code = $reason_code;
        return $this;
    }

    /**
     * Reason code, `AUTHORIZATION`, for a transaction state of `pending`.
     *
     * @return string
     */
    public function getReasonCode()
    {
        return $this->reason_code;
    }

    /**
     * [DEPRECATED] Reason code for the transaction state being Pending.Obsolete. use reason_code field instead.
     * Valid Values: ["AUTHORIZATION"]
     *
     * @param string $pending_reason
     *
     * @return $this
     */
    public function setPendingReason($pending_reason)
    {
        $this->pending_reason = $pending_reason;
        return $this;
    }

    /**
     * @deprecated  [DEPRECATED] Reason code for the transaction state being Pending.Obsolete. use reason_code field instead.
     *
     * @return string
     */
    public function getPendingReason()
    {
        return $this->pending_reason;
    }

    /**
     * The level of seller protection in force for the transaction. Only supported when the `payment_method` is set to `paypal`. Allowed values:<br>  `ELIGIBLE`- Merchant is protected by PayPal's Seller Protection Policy for Unauthorized Payments and Item Not Received.<br> `PARTIALLY_ELIGIBLE`- Merchant is protected by PayPal's Seller Protection Policy for Item Not Received or Unauthorized Payments. Refer to `protection_eligibility_type` for specifics. <br> `INELIGIBLE`- Merchant is not protected under the Seller Protection Policy.
     * Valid Values: ["ELIGIBLE", "PARTIALLY_ELIGIBLE", "INELIGIBLE"]
     *
     * @param string $protection_eligibility
     *
     * @return $this
     */
    public function setProtectionEligibility($protection_eligibility)
    {
        $this->protection_eligibility = $protection_eligibility;
        return $this;
    }

    /**
     * The level of seller protection in force for the transaction. Only supported when the `payment_method` is set to `paypal`. Allowed values:<br>  `ELIGIBLE`- Merchant is protected by PayPal's Seller Protection Policy for Unauthorized Payments and Item Not Received.<br> `PARTIALLY_ELIGIBLE`- Merchant is protected by PayPal's Seller Protection Policy for Item Not Received or Unauthorized Payments. Refer to `protection_eligibility_type` for specifics. <br> `INELIGIBLE`- Merchant is not protected under the Seller Protection Policy.
     *
     * @return string
     */
    public function getProtectionEligibility()
    {
        return $this->protection_eligibility;
    }

    /**
     * The kind of seller protection in force for the transaction. This property is returned only when the `protection_eligibility` property is set to `ELIGIBLE`or `PARTIALLY_ELIGIBLE`. Only supported when the `payment_method` is set to `paypal`. Allowed values:<br> `ITEM_NOT_RECEIVED_ELIGIBLE`- Sellers are protected against claims for items not received.<br> `UNAUTHORIZED_PAYMENT_ELIGIBLE`- Sellers are protected against claims for unauthorized payments.<br> One or both of the allowed values can be returned.
     * Valid Values: ["ITEM_NOT_RECEIVED_ELIGIBLE", "UNAUTHORIZED_PAYMENT_ELIGIBLE", "ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE"]
     *
     * @param string $protection_eligibility_type
     *
     * @return $this
     */
    public function setProtectionEligibilityType($protection_eligibility_type)
    {
        $this->protection_eligibility_type = $protection_eligibility_type;
        return $this;
    }

    /**
     * The kind of seller protection in force for the transaction. This property is returned only when the `protection_eligibility` property is set to `ELIGIBLE`or `PARTIALLY_ELIGIBLE`. Only supported when the `payment_method` is set to `paypal`. Allowed values:<br> `ITEM_NOT_RECEIVED_ELIGIBLE`- Sellers are protected against claims for items not received.<br> `UNAUTHORIZED_PAYMENT_ELIGIBLE`- Sellers are protected against claims for unauthorized payments.<br> One or both of the allowed values can be returned.
     *
     * @return string
     */
    public function getProtectionEligibilityType()
    {
        return $this->protection_eligibility_type;
    }

    /**
     * Fraud Management Filter (FMF) details applied for the payment that could result in accept, deny, or pending action. Returned in a payment response only if the merchant has enabled FMF in the profile settings and one of the fraud filters was triggered based on those settings. See [Fraud Management Filters Summary](https://developer.paypal.com/docs/classic/fmf/integration-guide/FMFSummary/) for more information.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\FmfDetails $fmf_details
     *
     * @return $this
     */
    public function setFmfDetails($fmf_details)
    {
        $this->fmf_details = $fmf_details;
        return $this;
    }

    /**
     * Fraud Management Filter (FMF) details applied for the payment that could result in accept, deny, or pending action. Returned in a payment response only if the merchant has enabled FMF in the profile settings and one of the fraud filters was triggered based on those settings. See [Fraud Management Filters Summary](https://developer.paypal.com/docs/classic/fmf/integration-guide/FMFSummary/) for more information.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\FmfDetails
     */
    public function getFmfDetails()
    {
        return $this->fmf_details;
    }

    /**
     * ID of the Payment resource that this transaction is based on.
     *
     * @param string $parent_payment
     *
     * @return $this
     */
    public function setParentPayment($parent_payment)
    {
        $this->parent_payment = $parent_payment;
        return $this;
    }

    /**
     * ID of the Payment resource that this transaction is based on.
     *
     * @return string
     */
    public function getParentPayment()
    {
        return $this->parent_payment;
    }

    /**
     * Response codes returned by the processor concerning the submitted payment. Only supported when the `payment_method` is set to `credit_card`.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ProcessorResponse $processor_response
     *
     * @return $this
     */
    public function setProcessorResponse($processor_response)
    {
        $this->processor_response = $processor_response;
        return $this;
    }

    /**
     * Response codes returned by the processor concerning the submitted payment. Only supported when the `payment_method` is set to `credit_card`.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\ProcessorResponse
     */
    public function getProcessorResponse()
    {
        return $this->processor_response;
    }

    /**
     * Authorization expiration time and date as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $valid_until
     *
     * @return $this
     */
    public function setValidUntil($valid_until)
    {
        $this->valid_until = $valid_until;
        return $this;
    }

    /**
     * Authorization expiration time and date as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getValidUntil()
    {
        return $this->valid_until;
    }

    /**
     * Time of authorization as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $create_time
     *
     * @return $this
     */
    public function setCreateTime($create_time)
    {
        $this->create_time = $create_time;
        return $this;
    }

    /**
     * Time of authorization as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Time that the resource was last updated.
     *
     * @param string $update_time
     *
     * @return $this
     */
    public function setUpdateTime($update_time)
    {
        $this->update_time = $update_time;
        return $this;
    }

    /**
     * Time that the resource was last updated.
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Retrieve details about a previously created authorization by passing the authorization_id in the request URI.
     *
     * @param string         $authorizationId
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return Authorization
     */
    public static function get($authorizationId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($authorizationId, 'authorizationId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/authorization/$authorizationId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Authorization();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Capture and process a previously created authorization by passing the authorization_id in the request URI. To use this request, the original payment call must have the intent set to authorize.
     *
     * @param Capture        $capture
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return Capture
     */
    public function capture($capture, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($capture, 'capture');
        $payLoad = $capture->toJSON();
        $json = self::executeCall(
            "/v1/payments/authorization/{$this->getId()}/capture",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Capture();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Void (cancel) a previously authorized payment by passing the authorization_id in the request URI. Note that a fully captured authorization cannot be voided.
     *
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return Authorization
     */
    public function void($apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/authorization/{$this->getId()}/void",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

    /**
     * Reauthorize a PayPal account payment by passing the authorization_id in the request URI. You should reauthorize a payment after the initial 3-day honor period to ensure that funds are still available. Request supports only amount field
     *
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return Authorization
     */
    public function reauthorize($apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/payments/authorization/{$this->getId()}/reauthorize",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

}
