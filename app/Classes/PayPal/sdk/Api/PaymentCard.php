<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PaymentCard
 *
 * A resource representing a payment card that can be used to fund a payment.
 *
 * @package PayPal\Api
 *
 * @property string              id
 * @property string              number
 * @property string              type
 * @property string              expire_month
 * @property string              expire_year
 * @property string              start_month
 * @property string              start_year
 * @property string              cvv2
 * @property string              first_name
 * @property string              last_name
 * @property string              billing_country
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Address billing_address
 * @property string              external_customer_id
 * @property string              status
 * @property string              valid_until
 * @property string              issue_number
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 */
class PaymentCard extends PayPalModel
{
    /**
     * ID of the credit card being saved for later use.
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
     * ID of the credit card being saved for later use.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Card number.
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
     * Card number.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Type of the Card.
     * Valid Values: ["VISA", "AMEX", "SOLO", "JCB", "STAR", "DELTA", "DISCOVER", "SWITCH", "MAESTRO", "CB_NATIONALE", "CONFINOGA", "COFIDIS", "ELECTRON", "CETELEM", "CHINA_UNION_PAY", "MASTERCARD"]
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
     * Type of the Card.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 2 digit card expiry month.
     *
     * @param string $expire_month
     *
     * @return $this
     */
    public function setExpireMonth($expire_month)
    {
        $this->expire_month = $expire_month;
        return $this;
    }

    /**
     * 2 digit card expiry month.
     *
     * @return string
     */
    public function getExpireMonth()
    {
        return $this->expire_month;
    }

    /**
     * 4 digit card expiry year
     *
     * @param string $expire_year
     *
     * @return $this
     */
    public function setExpireYear($expire_year)
    {
        $this->expire_year = $expire_year;
        return $this;
    }

    /**
     * 4 digit card expiry year
     *
     * @return string
     */
    public function getExpireYear()
    {
        return $this->expire_year;
    }

    /**
     * 2 digit card start month. Needed for UK Maestro Card.
     *
     * @param string $start_month
     *
     * @return $this
     */
    public function setStartMonth($start_month)
    {
        $this->start_month = $start_month;
        return $this;
    }

    /**
     * 2 digit card start month. Needed for UK Maestro Card.
     *
     * @return string
     */
    public function getStartMonth()
    {
        return $this->start_month;
    }

    /**
     * 4 digit card start year. Needed for UK Maestro Card.
     *
     * @param string $start_year
     *
     * @return $this
     */
    public function setStartYear($start_year)
    {
        $this->start_year = $start_year;
        return $this;
    }

    /**
     * 4 digit card start year. Needed for UK Maestro Card.
     *
     * @return string
     */
    public function getStartYear()
    {
        return $this->start_year;
    }

    /**
     * Card validation code. Only supported when making a Payment but not when saving a payment card for future use.
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
     * Card validation code. Only supported when making a Payment but not when saving a payment card for future use.
     *
     * @return string
     */
    public function getCvv2()
    {
        return $this->cvv2;
    }

    /**
     * Card holder's first name.
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
     * Card holder's first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Card holder's last name.
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
     * Card holder's last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * 2 letter country code
     *
     * @param string $billing_country
     *
     * @return $this
     */
    public function setBillingCountry($billing_country)
    {
        $this->billing_country = $billing_country;
        return $this;
    }

    /**
     * 2 letter country code
     *
     * @return string
     */
    public function getBillingCountry()
    {
        return $this->billing_country;
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
     * A unique identifier of the customer to whom this card account belongs to. Generated and provided by the facilitator. This is required when creating or using a stored funding instrument in vault.
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
     * A unique identifier of the customer to whom this card account belongs to. Generated and provided by the facilitator. This is required when creating or using a stored funding instrument in vault.
     *
     * @return string
     */
    public function getExternalCustomerId()
    {
        return $this->external_customer_id;
    }

    /**
     * State of the funding instrument.
     * Valid Values: ["EXPIRED", "ACTIVE"]
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * State of the funding instrument.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
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
     * Date/Time until this resource can be used fund a payment.
     *
     * @return string
     */
    public function getValidUntil()
    {
        return $this->valid_until;
    }

    /**
     * 1-2 digit card issue number. Needed for UK Maestro Card.
     *
     * @param string $issue_number
     *
     * @return $this
     */
    public function setIssueNumber($issue_number)
    {
        $this->issue_number = $issue_number;
        return $this;
    }

    /**
     * 1-2 digit card issue number. Needed for UK Maestro Card.
     *
     * @return string
     */
    public function getIssueNumber()
    {
        return $this->issue_number;
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
