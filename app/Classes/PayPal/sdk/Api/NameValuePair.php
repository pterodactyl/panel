<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class NameValuePair
 *
 * Used to define a type for name-value pairs.  The use of name value pairs in an API should be limited and approved by architecture.
 *
 * @package PayPal\Api
 *
 * @property string name
 * @property string value
 */
class NameValuePair extends PayPalModel
{
    /**
     * Key for the name value pair.  The value name types should be correlated
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
     * Key for the name value pair.  The value name types should be correlated
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Value for the name value pair.
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
     * Value for the name value pair.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

}
