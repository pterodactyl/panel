<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Converter\FormatConverter;
use Pterodactyl\Classes\PayPal\sdk\Validation\NumericValidator;

/**
 * Class Details
 *
 * Additional details of the payment amount.
 *
 * @package PayPal\Api
 *
 * @property string subtotal
 * @property string shipping
 * @property string tax
 * @property string handling_fee
 * @property string shipping_discount
 * @property string insurance
 * @property string gift_wrap
 * @property string fee
 */
class Details extends PayPalModel
{
    /**
     * Amount of the subtotal of the items. **Required** if line items are specified. 10 characters max, with support for 2 decimal places.
     *
     * @param string|double $subtotal
     *
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        NumericValidator::validate($subtotal, "Subtotal");
        $subtotal = FormatConverter::formatToPrice($subtotal);
        $this->subtotal = $subtotal;
        return $this;
    }

    /**
     * Amount of the subtotal of the items. **Required** if line items are specified. 10 characters max, with support for 2 decimal places.
     *
     * @return string
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * Amount charged for shipping. 10 characters max with support for 2 decimal places.
     *
     * @param string|double $shipping
     *
     * @return $this
     */
    public function setShipping($shipping)
    {
        NumericValidator::validate($shipping, "Shipping");
        $shipping = FormatConverter::formatToPrice($shipping);
        $this->shipping = $shipping;
        return $this;
    }

    /**
     * Amount charged for shipping. 10 characters max with support for 2 decimal places.
     *
     * @return string
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * Amount charged for tax. 10 characters max with support for 2 decimal places.
     *
     * @param string|double $tax
     *
     * @return $this
     */
    public function setTax($tax)
    {
        NumericValidator::validate($tax, "Tax");
        $tax = FormatConverter::formatToPrice($tax);
        $this->tax = $tax;
        return $this;
    }

    /**
     * Amount charged for tax. 10 characters max with support for 2 decimal places.
     *
     * @return string
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Amount being charged for the handling fee. Only supported when the `payment_method` is set to `paypal`.
     *
     * @param string|double $handling_fee
     *
     * @return $this
     */
    public function setHandlingFee($handling_fee)
    {
        NumericValidator::validate($handling_fee, "Handling Fee");
        $handling_fee = FormatConverter::formatToPrice($handling_fee);
        $this->handling_fee = $handling_fee;
        return $this;
    }

    /**
     * Amount being charged for the handling fee. Only supported when the `payment_method` is set to `paypal`.
     *
     * @return string
     */
    public function getHandlingFee()
    {
        return $this->handling_fee;
    }

    /**
     * Amount being discounted for the shipping fee. Only supported when the `payment_method` is set to `paypal`.
     *
     * @param string|double $shipping_discount
     *
     * @return $this
     */
    public function setShippingDiscount($shipping_discount)
    {
        NumericValidator::validate($shipping_discount, "Shipping Discount");
        $shipping_discount = FormatConverter::formatToPrice($shipping_discount);
        $this->shipping_discount = $shipping_discount;
        return $this;
    }

    /**
     * Amount being discounted for the shipping fee. Only supported when the `payment_method` is set to `paypal`.
     *
     * @return string
     */
    public function getShippingDiscount()
    {
        return $this->shipping_discount;
    }

    /**
     * Amount being charged for the insurance fee. Only supported when the `payment_method` is set to `paypal`.
     *
     * @param string|double $insurance
     *
     * @return $this
     */
    public function setInsurance($insurance)
    {
        NumericValidator::validate($insurance, "Insurance");
        $insurance = FormatConverter::formatToPrice($insurance);
        $this->insurance = $insurance;
        return $this;
    }

    /**
     * Amount being charged for the insurance fee. Only supported when the `payment_method` is set to `paypal`.
     *
     * @return string
     */
    public function getInsurance()
    {
        return $this->insurance;
    }

    /**
     * Amount being charged as gift wrap fee.
     *
     * @param string|double $gift_wrap
     *
     * @return $this
     */
    public function setGiftWrap($gift_wrap)
    {
        NumericValidator::validate($gift_wrap, "Gift Wrap");
        $gift_wrap = FormatConverter::formatToPrice($gift_wrap);
        $this->gift_wrap = $gift_wrap;
        return $this;
    }

    /**
     * Amount being charged as gift wrap fee.
     *
     * @return string
     */
    public function getGiftWrap()
    {
        return $this->gift_wrap;
    }

    /**
     * Fee charged by PayPal. In case of a refund, this is the fee amount refunded to the original receipient of the payment.
     *
     * @param string|double $fee
     *
     * @return $this
     */
    public function setFee($fee)
    {
        NumericValidator::validate($fee, "Fee");
        $fee = FormatConverter::formatToPrice($fee);
        $this->fee = $fee;
        return $this;
    }

    /**
     * Fee charged by PayPal. In case of a refund, this is the fee amount refunded to the original receipient of the payment.
     *
     * @return string
     */
    public function getFee()
    {
        return $this->fee;
    }

}
