<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Validation\UrlValidator;

/**
 * Class CurrencyConversion
 *
 * Object used to store the currency conversion rate.
 *
 * @package PayPal\Api
 *
 * @property string              conversion_date
 * @property string              from_currency
 * @property string              from_amount
 * @property string              to_currency
 * @property string              to_amount
 * @property string              conversion_type
 * @property bool                conversion_type_changeable
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 */
class CurrencyConversion extends PayPalModel
{
    /**
     * Date of validity for the conversion rate.
     *
     * @param string $conversion_date
     *
     * @return $this
     */
    public function setConversionDate($conversion_date)
    {
        $this->conversion_date = $conversion_date;
        return $this;
    }

    /**
     * Date of validity for the conversion rate.
     *
     * @return string
     */
    public function getConversionDate()
    {
        return $this->conversion_date;
    }

    /**
     * 3 letter currency code
     *
     * @param string $from_currency
     *
     * @return $this
     */
    public function setFromCurrency($from_currency)
    {
        $this->from_currency = $from_currency;
        return $this;
    }

    /**
     * 3 letter currency code
     *
     * @return string
     */
    public function getFromCurrency()
    {
        return $this->from_currency;
    }

    /**
     * Amount participating in currency conversion, set to 1 as default
     *
     * @param string $from_amount
     *
     * @return $this
     */
    public function setFromAmount($from_amount)
    {
        $this->from_amount = $from_amount;
        return $this;
    }

    /**
     * Amount participating in currency conversion, set to 1 as default
     *
     * @return string
     */
    public function getFromAmount()
    {
        return $this->from_amount;
    }

    /**
     * 3 letter currency code
     *
     * @param string $to_currency
     *
     * @return $this
     */
    public function setToCurrency($to_currency)
    {
        $this->to_currency = $to_currency;
        return $this;
    }

    /**
     * 3 letter currency code
     *
     * @return string
     */
    public function getToCurrency()
    {
        return $this->to_currency;
    }

    /**
     * Amount resulting from currency conversion.
     *
     * @param string $to_amount
     *
     * @return $this
     */
    public function setToAmount($to_amount)
    {
        $this->to_amount = $to_amount;
        return $this;
    }

    /**
     * Amount resulting from currency conversion.
     *
     * @return string
     */
    public function getToAmount()
    {
        return $this->to_amount;
    }

    /**
     * Field indicating conversion type applied.
     * Valid Values: ["PAYPAL", "VENDOR"]
     *
     * @param string $conversion_type
     *
     * @return $this
     */
    public function setConversionType($conversion_type)
    {
        $this->conversion_type = $conversion_type;
        return $this;
    }

    /**
     * Field indicating conversion type applied.
     *
     * @return string
     */
    public function getConversionType()
    {
        return $this->conversion_type;
    }

    /**
     * Allow Payer to change conversion type.
     *
     * @param bool $conversion_type_changeable
     *
     * @return $this
     */
    public function setConversionTypeChangeable($conversion_type_changeable)
    {
        $this->conversion_type_changeable = $conversion_type_changeable;
        return $this;
    }

    /**
     * Allow Payer to change conversion type.
     *
     * @return bool
     */
    public function getConversionTypeChangeable()
    {
        return $this->conversion_type_changeable;
    }

    /**
     * Base URL to web applications endpoint
     * Valid Values: ["https://www.paypal.com/{country_code}/webapps/xocspartaweb/webflow/sparta/proxwebflow", "https://www.paypal.com/{country_code}/proxflow"]
     *
     * @deprecated Not publicly available
     * @param string $web_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setWebUrl($web_url)
    {
        UrlValidator::validate($web_url, "WebUrl");
        $this->web_url = $web_url;
        return $this;
    }

    /**
     * Base URL to web applications endpoint
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getWebUrl()
    {
        return $this->web_url;
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
