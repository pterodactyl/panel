<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Converter\FormatConverter;
use Pterodactyl\Classes\PayPal\sdk\Validation\NumericValidator;

/**
 * Class InvoiceItem
 *
 * Information about a single line item.
 *
 * @package PayPal\Api
 *
 * @property string name
 * @property string description
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\number quantity
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency unit_price
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Tax tax
 * @property string date
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Cost discount
 */
class InvoiceItem extends PayPalModel
{
    /**
     * Name of the item. 60 characters max.
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
     * Name of the item. 60 characters max.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Description of the item. 1000 characters max.
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
     * Description of the item. 1000 characters max.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Quantity of the item. Range of 0 to 9999.999.
     *
     * @param string|double $quantity
     *
     * @return $this
     */
    public function setQuantity($quantity)
    {
        NumericValidator::validate($quantity, "Percent");
        $quantity = FormatConverter::formatToPrice($quantity);
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Quantity of the item. Range of 0 to 9999.999.
     *
     * @return string
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Unit price of the item. Range of -999999.99 to 999999.99.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $unit_price
     *
     * @return $this
     */
    public function setUnitPrice($unit_price)
    {
        $this->unit_price = $unit_price;
        return $this;
    }

    /**
     * Unit price of the item. Range of -999999.99 to 999999.99.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getUnitPrice()
    {
        return $this->unit_price;
    }

    /**
     * Tax associated with the item.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Tax $tax
     *
     * @return $this
     */
    public function setTax($tax)
    {
        $this->tax = $tax;
        return $this;
    }

    /**
     * Tax associated with the item.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Tax
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Date on which the item or service was provided. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Date on which the item or service was provided. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Item discount in percent or amount.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Cost $discount
     *
     * @return $this
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
        return $this;
    }

    /**
     * Item discount in percent or amount.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Cost
     */
    public function getDiscount()
    {
        return $this->discount;
    }

}
