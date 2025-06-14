<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class Measurement
 *
 * Measurement to represent item dimensions like length, width, height and weight etc.
 *
 * @package PayPal\Api
 *
 * @property string value
 * @property string unit
 */
class Measurement extends PayPalModel
{
    /**
     * Value this measurement represents.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Value this measurement represents.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Unit in which the value is represented.
     *
     * @param string $unit
     *
     * @return $this
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * Unit in which the value is represented.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

}
