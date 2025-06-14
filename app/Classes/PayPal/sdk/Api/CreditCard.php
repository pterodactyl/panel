<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class CreditCard
 *
 * @package    PayPal\Api
 *
 * @property string              number
 * @property string              type
 * @property int                 expire_month
 * @property int                 expire_year
 * @property string              cvv2
 * @property string              first_name
 * @property string              last_name
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Address billing_address
 * @property string              external_customer_id
 * @property string              state
 * @property string              valid_until
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 */
class CreditCard extends PayPalResourceModel
{
    /**
     * ID of the credit card. This ID is provided in the response when storing credit cards. **Required if using a stored credit card.**
     *
     * @deprecated Not publicly available
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
     * ID of the credit card. This ID is provided in the response when storing credit cards. **Required if using a stored credit card.**
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Credit card number. Numeric characters only with no spaces or punctuation. The string must conform with modulo and length required by each credit card type. *Redacted in responses.*
     *
     * @param string $number
     *
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * Credit card number. Numeric characters only with no spaces or punctuation. The string must conform with modulo and length required by each credit card type. *Redacted in responses.*
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Credit card type. Valid types are: `visa`, `mastercard`, `discover`, `amex`
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
     * Credit card type. Valid types are: `visa`, `mastercard`, `discover`, `amex`
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Expiration month with no leading zero. Acceptable values are 1 through 12.
     *
     * @param int $expire_month
     *
     * @return $this
     */
    public function setExpireMonth($expire_month)
    {
        $this->expire_month = $expire_month;
        return $this;
    }

    /**
     * Expiration month with no leading zero. Acceptable values are 1 through 12.
     *
     * @return int
     */
    public function getExpireMonth()
    {
        return $this->expire_month;
    }

    /**
     * 4-digit expiration year.
     *
     * @param int $expire_year
     *
     * @return $this
     */
    public function setExpireYear($expire_year)
    {
        $this->expire_year = $expire_year;
        return $this;
    }

    /**
     * 4-digit expiration year.
     *
     * @return int
     */
    public function getExpireYear()
    {
        return $this->expire_year;
    }

    /**
     * 3-4 digit card validation code.
     *
     * @param string $cvv2
     *
     * @return $this
     */
    public function setCvv2($cvv2)
    {
        $this->cvv2 = $cvv2;
        return $this;
    }

    /**
     * 3-4 digit card validation code.
     *
     * @return string
     */
    public function getCvv2()
    {
        return $this->cvv2;
    }

    /**
     * Cardholder's first name.
     *
     * @param string $first_name
     *
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * Cardholder's first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Cardholder's last name.
     *
     * @param string $last_name
     *
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Cardholder's last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Billing Address associated with this card.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Address $billing_address
     *
     * @return $this
     */
    public function setBillingAddress($billing_address)
    {
        $this->billing_address = $billing_address;
        return $this;
    }

    /**
     * Billing Address associated with this card.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Address
     */
    public function getBillingAddress()
    {
        return $this->billing_address;
    }

    /**
     * A unique identifier of the customer to whom this bank account belongs. Generated and provided by the facilitator. **This is now used in favor of `payer_id` when creating or using a stored funding instrument in the vault.**
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
     * A unique identifier of the customer to whom this bank account belongs. Generated and provided by the facilitator. **This is now used in favor of `payer_id` when creating or using a stored funding instrument in the vault.**
     *
     * @return string
     */
    public function getExternalCustomerId()
    {
        return $this->external_customer_id;
    }

    /**
     * A user provided, optional convenvience field that functions as a unique identifier for the merchant on behalf of whom this credit card is being stored for. Note that this has no relation to PayPal merchant id
     *
     * @param string $merchant_id
     *
     * @return $this
     */
    public function setMerchantId($merchant_id)
    {
        $this->merchant_id = $merchant_id;
        return $this;
    }

    /**
     * A user provided, optional convenvience field that functions as a unique identifier for the merchant on behalf of whom this credit card is being stored for. Note that this has no relation to PayPal merchant id
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * A unique identifier that you can assign and track when storing a credit card or using a stored credit card. This ID can help to avoid unintentional use or misuse of credit cards. This ID can be any value you would like to associate with the saved card, such as a UUID, username, or email address. Required when using a stored credit card if a payer_id was originally provided when storing the credit card in vault.
     *
     * @deprecated This is being deprecated in favor of the `external_customer_id` property.
     * @param string $payer_id
     *
     * @return $this
     */
    public function setPayerId($payer_id)
    {
        $this->payer_id = $payer_id;
        return $this;
    }

    /**
     * A unique identifier that you can assign and track when storing a credit card or using a stored credit card. This ID can help to avoid unintentional use or misuse of credit cards. This ID can be any value you would like to associate with the saved card, such as a UUID, username, or email address. Required when using a stored credit card if a payer_id was originally provided when storing the credit card in vault.
     *
     * @deprecated This is being deprecated in favor of the `external_customer_id` property.
     * @return string
     */
    public function getPayerId()
    {
        return $this->payer_id;
    }

    /**
     * A unique identifier of the bank account resource. Generated and provided by the facilitator so it can be used to restrict the usage of the bank account to the specific merchant.
     *
     * @param string $external_card_id
     *
     * @return $this
     */
    public function setExternalCardId($external_card_id)
    {
        $this->external_card_id = $external_card_id;
        return $this;
    }

    /**
     * A unique identifier of the bank account resource. Generated and provided by the facilitator so it can be used to restrict the usage of the bank account to the specific merchant.
     *
     * @return string
     */
    public function getExternalCardId()
    {
        return $this->external_card_id;
    }

    /**
     * State of the funding instrument.
     * Valid Values: ["expired", "ok"]
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
     * State of the credit card funding instrument.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Funding instrument expiration date.
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
     * Resource creation time  as ISO8601 date-time format (ex: 1994-11-05T13:15:30Z) that indicates creation time.
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Resource creation time  as ISO8601 date-time format (ex: 1994-11-05T13:15:30Z) that indicates the updation time.
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
     * Resource creation time  as ISO8601 date-time format (ex: 1994-11-05T13:15:30Z) that indicates the updation time.
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Date/Time until this resource can be used fund a payment.
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
     * Funding instrument expiration date.
     *
     * @return string
     */
    public function getValidUntil()
    {
        return $this->valid_until;
    }

    /**
     * Creates a new Credit Card Resource (aka Tokenize).
     *
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return CreditCard
     */
    public function create($apiContext = null, $restCall = null)
    {
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/vault/credit-cards",
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
     * Obtain the Credit Card resource for the given identifier.
     *
     * @param string         $creditCardId
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return CreditCard
     */
    public static function get($creditCardId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($creditCardId, 'creditCardId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/vault/credit-cards/$creditCardId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new CreditCard();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Delete the Credit Card resource for the given identifier.
     *
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function delete($apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        $payLoad = "";
        self::executeCall(
            "/v1/vault/credit-cards/{$this->getId()}",
            "DELETE",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Update information in a previously saved card. Only the modified fields need to be passed in the request.
     *
     * @param PatchRequest   $patchRequest
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return CreditCard
     */
    public function update($patchRequest, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($patchRequest, 'patch');
        $payload = $patchRequest->toJSON();
        $json = self::executeCall(
            "/v1/vault/credit-cards/{$this->getId()}",
            "PATCH",
            $payload,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

    /**
     * Retrieves a list of Credit Card resources.
     *
     * @param array          $params
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return CreditCardList
     */
    public static function all($params, $apiContext = null, $restCall = null)
    {
        if (is_null($params)) {
            $params = array();
        }
        ArgumentValidator::validate($params, 'params');
        $payLoad = "";
        $allowedParams = array(
            'page_size' => 1,
            'page' => 1,
            'start_time' => 1,
            'end_time' => 1,
            'sort_order' => 1,
            'sort_by' => 1,
            'merchant_id' => 1,
            'external_card_id' => 1,
            'external_customer_id' => 1,
        );
        $json = self::executeCall(
            "/v1/vault/credit-cards" . "?" . http_build_query(array_intersect_key($params, $allowedParams)),
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new CreditCardList();
        $ret->fromJson($json);
        return $ret;
    }

}
