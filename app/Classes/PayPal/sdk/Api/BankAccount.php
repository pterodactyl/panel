<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class BankAccount
 *
 * A resource representing a bank account that can be used to fund a payment.
 *
 * @package PayPal\Api
 *
 * @property string              account_number
 * @property string              account_number_type
 * @property string              routing_number
 * @property string              account_type
 * @property string              account_name
 * @property string              check_type
 * @property string              auth_type
 * @property string              auth_capture_timestamp
 * @property string              bank_name
 * @property string              country_code
 * @property string              first_name
 * @property string              last_name
 * @property string              birth_date
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Address billing_address
 * @property string              state
 * @property string              confirmation_status
 * @property string              payer_id
 * @property string              external_customer_id
 * @property string              merchant_id
 * @property string              create_time
 * @property string              update_time
 * @property string              valid_until
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 */
class BankAccount extends PayPalModel
{
    /**
     * ID of the bank account being saved for later use.
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
     * ID of the bank account being saved for later use.
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Account number in either IBAN (max length 34) or BBAN (max length 17) format.
     *
     * @param string $account_number
     *
     * @return $this
     */
    public function setAccountNumber($account_number)
    {
        $this->account_number = $account_number;
        return $this;
    }

    /**
     * Account number in either IBAN (max length 34) or BBAN (max length 17) format.
     *
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->account_number;
    }

    /**
     * Type of the bank account number (International or Basic Bank Account Number). For more information refer to http://en.wikipedia.org/wiki/International_Bank_Account_Number.
     * Valid Values: ["BBAN", "IBAN"]
     *
     * @param string $account_number_type
     *
     * @return $this
     */
    public function setAccountNumberType($account_number_type)
    {
        $this->account_number_type = $account_number_type;
        return $this;
    }

    /**
     * Type of the bank account number (International or Basic Bank Account Number). For more information refer to http://en.wikipedia.org/wiki/International_Bank_Account_Number.
     *
     * @return string
     */
    public function getAccountNumberType()
    {
        return $this->account_number_type;
    }

    /**
     * Routing transit number (aka Bank Code) of the bank (typically for domestic use only - for international use, IBAN includes bank code). For more information refer to http://en.wikipedia.org/wiki/Bank_code.
     *
     * @param string $routing_number
     *
     * @return $this
     */
    public function setRoutingNumber($routing_number)
    {
        $this->routing_number = $routing_number;
        return $this;
    }

    /**
     * Routing transit number (aka Bank Code) of the bank (typically for domestic use only - for international use, IBAN includes bank code). For more information refer to http://en.wikipedia.org/wiki/Bank_code.
     *
     * @return string
     */
    public function getRoutingNumber()
    {
        return $this->routing_number;
    }

    /**
     * Type of the bank account.
     * Valid Values: ["CHECKING", "SAVINGS"]
     *
     * @param string $account_type
     *
     * @return $this
     */
    public function setAccountType($account_type)
    {
        $this->account_type = $account_type;
        return $this;
    }

    /**
     * Type of the bank account.
     *
     * @return string
     */
    public function getAccountType()
    {
        return $this->account_type;
    }

    /**
     * A customer designated name.
     *
     * @param string $account_name
     *
     * @return $this
     */
    public function setAccountName($account_name)
    {
        $this->account_name = $account_name;
        return $this;
    }

    /**
     * A customer designated name.
     *
     * @return string
     */
    public function getAccountName()
    {
        return $this->account_name;
    }

    /**
     * Type of the check when this information was obtained through a check by the facilitator or merchant.
     * Valid Values: ["PERSONAL", "COMPANY"]
     *
     * @param string $check_type
     *
     * @return $this
     */
    public function setCheckType($check_type)
    {
        $this->check_type = $check_type;
        return $this;
    }

    /**
     * Type of the check when this information was obtained through a check by the facilitator or merchant.
     *
     * @return string
     */
    public function getCheckType()
    {
        return $this->check_type;
    }

    /**
     * How the check was obtained from the customer, if check was the source of the information provided.
     * Valid Values: ["CCD", "PPD", "TEL", "POP", "ARC", "RCK", "WEB"]
     *
     * @param string $auth_type
     *
     * @return $this
     */
    public function setAuthType($auth_type)
    {
        $this->auth_type = $auth_type;
        return $this;
    }

    /**
     * How the check was obtained from the customer, if check was the source of the information provided.
     *
     * @return string
     */
    public function getAuthType()
    {
        return $this->auth_type;
    }

    /**
     * Time at which the authorization (or check) was captured. Use this field if the user authorization needs to be captured due to any privacy requirements.
     *
     * @param string $auth_capture_timestamp
     *
     * @return $this
     */
    public function setAuthCaptureTimestamp($auth_capture_timestamp)
    {
        $this->auth_capture_timestamp = $auth_capture_timestamp;
        return $this;
    }

    /**
     * Time at which the authorization (or check) was captured. Use this field if the user authorization needs to be captured due to any privacy requirements.
     *
     * @return string
     */
    public function getAuthCaptureTimestamp()
    {
        return $this->auth_capture_timestamp;
    }

    /**
     * Name of the bank.
     *
     * @param string $bank_name
     *
     * @return $this
     */
    public function setBankName($bank_name)
    {
        $this->bank_name = $bank_name;
        return $this;
    }

    /**
     * Name of the bank.
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * 2 letter country code of the Bank.
     *
     * @param string $country_code
     *
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * 2 letter country code of the Bank.
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * Account holder's first name.
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
     * Account holder's first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Account holder's last name.
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
     * Account holder's last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Birth date of the bank account holder.
     *
     * @param string $birth_date
     *
     * @return $this
     */
    public function setBirthDate($birth_date)
    {
        $this->birth_date = $birth_date;
        return $this;
    }

    /**
     * Birth date of the bank account holder.
     *
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * Billing address.
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
     * Billing address.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Address
     */
    public function getBillingAddress()
    {
        return $this->billing_address;
    }

    /**
     * State of this funding instrument.
     * Valid Values: ["ACTIVE", "INACTIVE", "DELETED"]
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
     * State of this funding instrument.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Confirmation status of a bank account.
     * Valid Values: ["UNCONFIRMED", "CONFIRMED"]
     *
     * @param string $confirmation_status
     *
     * @return $this
     */
    public function setConfirmationStatus($confirmation_status)
    {
        $this->confirmation_status = $confirmation_status;
        return $this;
    }

    /**
     * Confirmation status of a bank account.
     *
     * @return string
     */
    public function getConfirmationStatus()
    {
        return $this->confirmation_status;
    }

    /**
     * [DEPRECATED] Use external_customer_id instead.
     *
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
     * @deprecated  [DEPRECATED] Use external_customer_id instead.
     *
     * @return string
     */
    public function getPayerId()
    {
        return $this->payer_id;
    }

    /**
     * A unique identifier of the customer to whom this bank account belongs to. Generated and provided by the facilitator. This is required when creating or using a stored funding instrument in vault.
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
     * A unique identifier of the customer to whom this bank account belongs to. Generated and provided by the facilitator. This is required when creating or using a stored funding instrument in vault.
     *
     * @return string
     */
    public function getExternalCustomerId()
    {
        return $this->external_customer_id;
    }

    /**
     * A unique identifier of the merchant for which this bank account has been stored for. Generated and provided by the facilitator so it can be used to restrict the usage of the bank account to the specific merchnt.
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
     * A unique identifier of the merchant for which this bank account has been stored for. Generated and provided by the facilitator so it can be used to restrict the usage of the bank account to the specific merchnt.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Time the resource was created.
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
     * Time the resource was created.
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Time the resource was last updated.
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
     * Time the resource was last updated.
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Date/Time until this resource can be used to fund a payment.
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
     * Date/Time until this resource can be used to fund a payment.
     *
     * @return string
     */
    public function getValidUntil()
    {
        return $this->valid_until;
    }

    /**
     * Sets Links
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Links[] $links
     *
     * @return $this
     */
    public function setLinks($links)
    {
        $this->links = $links;
        return $this;
    }

    /**
     * Gets Links
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Links[]
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Append Links to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Links $links
     * @return $this
     */
    public function addLink($links)
    {
        if (!$this->getLinks()) {
            return $this->setLinks(array($links));
        } else {
            return $this->setLinks(
                array_merge($this->getLinks(), array($links))
            );
        }
    }

    /**
     * Remove Links from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Links $links
     * @return $this
     */
    public function removeLink($links)
    {
        return $this->setLinks(
            array_diff($this->getLinks(), array($links))
        );
    }

}
