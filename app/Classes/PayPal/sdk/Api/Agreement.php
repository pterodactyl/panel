<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalConstants;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class Agreement
 *
 * A resource representing an agreement.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string state
 * @property string name
 * @property string description
 * @property string start_date
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Payer payer
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Address shipping_address
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\MerchantPreferences override_merchant_preferences
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\OverrideChargeModel[] override_charge_models
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Plan plan
 * @property string create_time
 * @property string update_time
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\AgreementDetails agreement_details
 */
class Agreement extends PayPalResourceModel
{
    /**
     * Identifier of the agreement.
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
     * Identifier of the agreement.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * State of the agreement.
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
     * State of the agreement.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Name of the agreement.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Name of the agreement.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Description of the agreement.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description of the agreement.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Start date of the agreement. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $start_date
     *
     * @return $this
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
        return $this;
    }

    /**
     * Start date of the agreement. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Details of the buyer who is enrolling in this agreement. This information is gathered from execution of the approval URL.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Payer $payer
     *
     * @return $this
     */
    public function setPayer($payer)
    {
        $this->payer = $payer;
        return $this;
    }

    /**
     * Details of the buyer who is enrolling in this agreement. This information is gathered from execution of the approval URL.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Payer
     */
    public function getPayer()
    {
        return $this->payer;
    }

    /**
     * Shipping address object of the agreement, which should be provided if it is different from the default address.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Address $shipping_address
     *
     * @return $this
     */
    public function setShippingAddress($shipping_address)
    {
        $this->shipping_address = $shipping_address;
        return $this;
    }

    /**
     * Shipping address object of the agreement, which should be provided if it is different from the default address.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Address
     */
    public function getShippingAddress()
    {
        return $this->shipping_address;
    }

    /**
     * Default merchant preferences from the billing plan are used, unless override preferences are provided here.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\MerchantPreferences $override_merchant_preferences
     *
     * @return $this
     */
    public function setOverrideMerchantPreferences($override_merchant_preferences)
    {
        $this->override_merchant_preferences = $override_merchant_preferences;
        return $this;
    }

    /**
     * Default merchant preferences from the billing plan are used, unless override preferences are provided here.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\MerchantPreferences
     */
    public function getOverrideMerchantPreferences()
    {
        return $this->override_merchant_preferences;
    }

    /**
     * Array of override_charge_model for this agreement if needed to change the default models from the billing plan.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\OverrideChargeModel[] $override_charge_models
     *
     * @return $this
     */
    public function setOverrideChargeModels($override_charge_models)
    {
        $this->override_charge_models = $override_charge_models;
        return $this;
    }

    /**
     * Array of override_charge_model for this agreement if needed to change the default models from the billing plan.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\OverrideChargeModel[]
     */
    public function getOverrideChargeModels()
    {
        return $this->override_charge_models;
    }

    /**
     * Append OverrideChargeModels to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\OverrideChargeModel $overrideChargeModel
     * @return $this
     */
    public function addOverrideChargeModel($overrideChargeModel)
    {
        if (!$this->getOverrideChargeModels()) {
            return $this->setOverrideChargeModels(array($overrideChargeModel));
        } else {
            return $this->setOverrideChargeModels(
                array_merge($this->getOverrideChargeModels(), array($overrideChargeModel))
            );
        }
    }

    /**
     * Remove OverrideChargeModels from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\OverrideChargeModel $overrideChargeModel
     * @return $this
     */
    public function removeOverrideChargeModel($overrideChargeModel)
    {
        return $this->setOverrideChargeModels(
            array_diff($this->getOverrideChargeModels(), array($overrideChargeModel))
        );
    }

    /**
     * Plan details for this agreement.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Plan $plan
     *
     * @return $this
     */
    public function setPlan($plan)
    {
        $this->plan = $plan;
        return $this;
    }

    /**
     * Plan details for this agreement.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Plan
     */
    public function getPlan()
    {
        return $this->plan;
    }

    /**
     * Date and time that this resource was created. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
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
     * Date and time that this resource was created. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Date and time that this resource was updated. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
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
     * Date and time that this resource was updated. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Agreement Details
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\AgreementDetails $agreement_details
     *
     * @return $this
     */
    public function setAgreementDetails($agreement_details)
    {
        $this->agreement_details = $agreement_details;
        return $this;
    }

    /**
     * Agreement Details
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\AgreementDetails
     */
    public function getAgreementDetails()
    {
        return $this->agreement_details;
    }

    /**
     * Get Approval Link
     *
     * @return null|string
     */
    public function getApprovalLink()
    {
        return $this->getLink(PayPalConstants::APPROVAL_URL);
    }

    /**
     * Create a new billing agreement by passing the details for the agreement, including the name, description, start date, payer, and billing plan in the request JSON.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Agreement
     */
    public function create($apiContext = null, $restCall = null)
    {
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/payments/billing-agreements/",
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
     * Execute a billing agreement after buyer approval by passing the payment token to the request URI.
     *
     * @param  $paymentToken
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Agreement
     */
    public function execute($paymentToken, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($paymentToken, 'paymentToken');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/billing-agreements/$paymentToken/agreement-execute",
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
     * Retrieve details for a particular billing agreement by passing the ID of the agreement to the request URI.
     *
     * @param string $agreementId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Agreement
     */
    public static function get($agreementId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($agreementId, 'agreementId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/billing-agreements/$agreementId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Agreement();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Update details of a billing agreement, such as the description, shipping address, and start date, by passing the ID of the agreement to the request URI.
     *
     * @param PatchRequest $patchRequest
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function update($patchRequest, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($patchRequest, 'patchRequest');
        $payLoad = $patchRequest->toJSON();
        self::executeCall(
            "/v1/payments/billing-agreements/{$this->getId()}",
            "PATCH",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Suspend a particular billing agreement by passing the ID of the agreement to the request URI.
     *
     * @param AgreementStateDescriptor $agreementStateDescriptor
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function suspend($agreementStateDescriptor, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($agreementStateDescriptor, 'agreementStateDescriptor');
        $payLoad = $agreementStateDescriptor->toJSON();
        self::executeCall(
            "/v1/payments/billing-agreements/{$this->getId()}/suspend",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Reactivate a suspended billing agreement by passing the ID of the agreement to the appropriate URI. In addition, pass an agreement_state_descriptor object in the request JSON that includes a note about the reason for changing the state of the agreement and the amount and currency for the agreement.
     *
     * @param AgreementStateDescriptor $agreementStateDescriptor
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function reActivate($agreementStateDescriptor, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($agreementStateDescriptor, 'agreementStateDescriptor');
        $payLoad = $agreementStateDescriptor->toJSON();
        self::executeCall(
            "/v1/payments/billing-agreements/{$this->getId()}/re-activate",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Cancel a billing agreement by passing the ID of the agreement to the request URI. In addition, pass an agreement_state_descriptor object in the request JSON that includes a note about the reason for changing the state of the agreement and the amount and currency for the agreement.
     *
     * @param AgreementStateDescriptor $agreementStateDescriptor
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function cancel($agreementStateDescriptor, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($agreementStateDescriptor, 'agreementStateDescriptor');
        $payLoad = $agreementStateDescriptor->toJSON();
        self::executeCall(
            "/v1/payments/billing-agreements/{$this->getId()}/cancel",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Bill an outstanding amount for an agreement by passing the ID of the agreement to the request URI. In addition, pass an agreement_state_descriptor object in the request JSON that includes a note about the reason for changing the state of the agreement and the amount and currency for the agreement.
     *
     * @param AgreementStateDescriptor $agreementStateDescriptor
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function billBalance($agreementStateDescriptor, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($agreementStateDescriptor, 'agreementStateDescriptor');
        $payLoad = $agreementStateDescriptor->toJSON();
        self::executeCall(
            "/v1/payments/billing-agreements/{$this->getId()}/bill-balance",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Set the balance for an agreement by passing the ID of the agreement to the request URI. In addition, pass a common_currency object in the request JSON that specifies the currency type and value of the balance.
     *
     * @param Currency $currency
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function setBalance($currency, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($currency, 'currency');
        $payLoad = $currency->toJSON();
        self::executeCall(
            "/v1/payments/billing-agreements/{$this->getId()}/set-balance",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * List transactions for a billing agreement by passing the ID of the agreement, as well as the start and end dates of the range of transactions to list, to the request URI.
     *
     * @deprecated Please use searchTransactions Instead
     * @param string $agreementId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return AgreementTransactions
     */
    public static function transactions($agreementId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($agreementId, 'agreementId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/billing-agreements/$agreementId/transactions",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new AgreementTransactions();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * List transactions for a billing agreement by passing the ID of the agreement, as well as the start and end dates of the range of transactions to list, to the request URI.
     *
     * @param string $agreementId
     * @param array $params Parameters for search string. Options: start_date, and end_date
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return AgreementTransactions
     */
    public static function searchTransactions($agreementId, $params = array(), $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($agreementId, 'agreementId');
        ArgumentValidator::validate($params, 'params');

        $allowedParams = array(
            'start_date' => 1,
            'end_date' => 1,
        );

        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/billing-agreements/$agreementId/transactions?" . http_build_query(array_intersect_key($params, $allowedParams)),
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new AgreementTransactions();
        $ret->fromJson($json);
        return $ret;
    }

}
