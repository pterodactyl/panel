<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class Plan
 *
 * Billing plan resource that will be used to create a billing agreement.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string name
 * @property string description
 * @property string type
 * @property string state
 * @property string create_time
 * @property string update_time
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\PaymentDefinition[] payment_definitions
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Terms[] terms
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\MerchantPreferences merchant_preferences
 */
class Plan extends PayPalResourceModel
{
    /**
     * Identifier of the billing plan. 128 characters max.
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
     * Identifier of the billing plan. 128 characters max.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Name of the billing plan. 128 characters max.
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
     * Name of the billing plan. 128 characters max.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Description of the billing plan. 128 characters max.
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
     * Description of the billing plan. 128 characters max.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Type of the billing plan. Allowed values: `FIXED`, `INFINITE`.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Type of the billing plan. Allowed values: `FIXED`, `INFINITE`.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Status of the billing plan. Allowed values: `CREATED`, `ACTIVE`, `INACTIVE`, and `DELETED`.
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
     * Status of the billing plan. Allowed values: `CREATED`, `ACTIVE`, `INACTIVE`, and `DELETED`.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Time when the billing plan was created. Format YYYY-MM-DDTimeTimezone, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
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
     * Time when the billing plan was created. Format YYYY-MM-DDTimeTimezone, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Time when this billing plan was updated. Format YYYY-MM-DDTimeTimezone, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
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
     * Time when this billing plan was updated. Format YYYY-MM-DDTimeTimezone, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Array of payment definitions for this billing plan.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PaymentDefinition[] $payment_definitions
     *
     * @return $this
     */
    public function setPaymentDefinitions($payment_definitions)
    {
        $this->payment_definitions = $payment_definitions;
        return $this;
    }

    /**
     * Array of payment definitions for this billing plan.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\PaymentDefinition[]
     */
    public function getPaymentDefinitions()
    {
        return $this->payment_definitions;
    }

    /**
     * Append PaymentDefinitions to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PaymentDefinition $paymentDefinition
     * @return $this
     */
    public function addPaymentDefinition($paymentDefinition)
    {
        if (!$this->getPaymentDefinitions()) {
            return $this->setPaymentDefinitions(array($paymentDefinition));
        } else {
            return $this->setPaymentDefinitions(
                array_merge($this->getPaymentDefinitions(), array($paymentDefinition))
            );
        }
    }

    /**
     * Remove PaymentDefinitions from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PaymentDefinition $paymentDefinition
     * @return $this
     */
    public function removePaymentDefinition($paymentDefinition)
    {
        return $this->setPaymentDefinitions(
            array_diff($this->getPaymentDefinitions(), array($paymentDefinition))
        );
    }

    /**
     * Array of terms for this billing plan.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Terms[] $terms
     *
     * @return $this
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
        return $this;
    }

    /**
     * Array of terms for this billing plan.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Terms[]
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Append Terms to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Terms $terms
     * @return $this
     */
    public function addTerm($terms)
    {
        if (!$this->getTerms()) {
            return $this->setTerms(array($terms));
        } else {
            return $this->setTerms(
                array_merge($this->getTerms(), array($terms))
            );
        }
    }

    /**
     * Remove Terms from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Terms $terms
     * @return $this
     */
    public function removeTerm($terms)
    {
        return $this->setTerms(
            array_diff($this->getTerms(), array($terms))
        );
    }

    /**
     * Specific preferences such as: set up fee, max fail attempts, autobill amount, and others that are configured for this billing plan.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\MerchantPreferences $merchant_preferences
     *
     * @return $this
     */
    public function setMerchantPreferences($merchant_preferences)
    {
        $this->merchant_preferences = $merchant_preferences;
        return $this;
    }

    /**
     * Specific preferences such as: set up fee, max fail attempts, autobill amount, and others that are configured for this billing plan.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\MerchantPreferences
     */
    public function getMerchantPreferences()
    {
        return $this->merchant_preferences;
    }

    /**
     * Retrieve the details for a particular billing plan by passing the billing plan ID to the request URI.
     *
     * @param string $planId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Plan
     */
    public static function get($planId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($planId, 'planId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/billing-plans/$planId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Plan();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Create a new billing plan by passing the details for the plan, including the plan name, description, and type, to the request URI.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Plan
     */
    public function create($apiContext = null, $restCall = null)
    {
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/payments/billing-plans/",
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
     * Replace specific fields within a billing plan by passing the ID of the billing plan to the request URI. In addition, pass a patch object in the request JSON that specifies the operation to perform, field to update, and new value for each update.
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
            "/v1/payments/billing-plans/{$this->getId()}",
            "PATCH",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Delete a billing plan by passing the ID of the billing plan to the request URI.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function delete($apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        $patchRequest = new PatchRequest();
        $patch = new Patch();
        $value = new PayPalModel('{
            "state":"DELETED"
        }');
        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest->addPatch($patch);
        return $this->update($patchRequest, $apiContext, $restCall);
    }

    /**
     * List billing plans according to optional query string parameters specified.
     *
     * @param array $params
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return PlanList
     */
    public static function all($params, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($params, 'params');
        $payLoad = "";
        $allowedParams = array(
            'page_size' => 1,
            'status' => 1,
            'page' => 1,
            'total_required' => 1
        );
        $json = self::executeCall(
            "/v1/payments/billing-plans/" . "?" . http_build_query(array_intersect_key($params, $allowedParams)),
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PlanList();
        $ret->fromJson($json);
        return $ret;
    }

}
