<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Converter\FormatConverter;
use Pterodactyl\Classes\PayPal\sdk\Validation\NumericValidator;

/**
 * Class Tax
 *
 * Tax information.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string name
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\number percent
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency amount
 */
class Tax extends PayPalModel
{
    /**
     * Identifier of the resource.
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
     * Identifier of the resource.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Name of the tax. 10 characters max.
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
     * Name of the tax. 10 characters max.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Rate of the specified tax. Range of 0.001 to 99.999.
     *
     * @param string|double $percent
     *
     * @return $this
     */
    public function setPercent($percent)
    {
        NumericValidator::validate($percent, "Percent");
        $percent = FormatConverter::formatToPrice($percent);
        $this->percent = $percent;
        return $this;
    }

    /**
     * Rate of the specified tax. Range of 0.001 to 99.999.
     *
     * @return string
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * Tax in the form of money. Cannot be specified in a request.
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
     * Tax in the form of money. Cannot be specified in a request.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

}
