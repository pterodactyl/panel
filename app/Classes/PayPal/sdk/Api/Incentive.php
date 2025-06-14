<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Validation\UrlValidator;

/**
 * Class Incentive
 *
 * A resource representing a incentive.
 *
 * @package PayPal\Api
 *
 * @property string               id
 * @property string               code
 * @property string               name
 * @property string               description
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency minimum_purchase_amount
 * @property string               logo_image_url
 * @property string               expiry_date
 * @property string               type
 * @property string               terms
 */
class Incentive extends PayPalModel
{
    /**
     * Identifier of the instrument in PayPal Wallet
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
     * Identifier of the instrument in PayPal Wallet
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Code that identifies the incentive.
     *
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Code that identifies the incentive.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Name of the incentive.
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
     * Name of the incentive.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Description of the incentive.
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
     * Description of the incentive.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Indicates incentive is applicable for this minimum purchase amount.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $minimum_purchase_amount
     *
     * @return $this
     */
    public function setMinimumPurchaseAmount($minimum_purchase_amount)
    {
        $this->minimum_purchase_amount = $minimum_purchase_amount;
        return $this;
    }

    /**
     * Indicates incentive is applicable for this minimum purchase amount.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getMinimumPurchaseAmount()
    {
        return $this->minimum_purchase_amount;
    }

    /**
     * Logo image url for the incentive.
     *
     * @param string $logo_image_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setLogoImageUrl($logo_image_url)
    {
        UrlValidator::validate($logo_image_url, "LogoImageUrl");
        $this->logo_image_url = $logo_image_url;
        return $this;
    }

    /**
     * Logo image url for the incentive.
     *
     * @return string
     */
    public function getLogoImageUrl()
    {
        return $this->logo_image_url;
    }

    /**
     * expiry date of the incentive.
     *
     * @param string $expiry_date
     *
     * @return $this
     */
    public function setExpiryDate($expiry_date)
    {
        $this->expiry_date = $expiry_date;
        return $this;
    }

    /**
     * expiry date of the incentive.
     *
     * @return string
     */
    public function getExpiryDate()
    {
        return $this->expiry_date;
    }

    /**
     * Specifies type of incentive
     * Valid Values: ["COUPON", "GIFT_CARD", "MERCHANT_SPECIFIC_BALANCE", "VOUCHER"]
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
     * Specifies type of incentive
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * URI to the associated terms
     *
     * @param string $terms
     *
     * @return $this
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
        return $this;
    }

    /**
     * URI to the associated terms
     *
     * @return string
     */
    public function getTerms()
    {
        return $this->terms;
    }

}
