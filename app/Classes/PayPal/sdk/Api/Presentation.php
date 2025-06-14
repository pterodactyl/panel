<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class Presentation
 *
 * Parameters for style and presentation.
 *
 * @package PayPal\Api
 *
 * @property string brand_name
 * @property string logo_image
 * @property string locale_code
 */
class Presentation extends PayPalModel
{
    /**
     * A label that overrides the business name in the PayPal account on the PayPal pages.
     *
     *
     * @param string $brand_name
     *
     * @return $this
     */
    public function setBrandName($brand_name)
    {
        $this->brand_name = $brand_name;
        return $this;
    }

    /**
     * A label that overrides the business name in the PayPal account on the PayPal pages.
     *
     * @return string
     */
    public function getBrandName()
    {
        return $this->brand_name;
    }

    /**
     * A URL to logo image. Allowed vaues: `.gif`, `.jpg`, or `.png`.
     *
     *
     * @param string $logo_image
     *
     * @return $this
     */
    public function setLogoImage($logo_image)
    {
        $this->logo_image = $logo_image;
        return $this;
    }

    /**
     * A URL to logo image. Allowed vaues: `.gif`, `.jpg`, or `.png`.
     *
     * @return string
     */
    public function getLogoImage()
    {
        return $this->logo_image;
    }

    /**
     * Locale of pages displayed by PayPal payment experience.
     *
     *
     * @param string $locale_code
     *
     * @return $this
     */
    public function setLocaleCode($locale_code)
    {
        $this->locale_code = $locale_code;
        return $this;
    }

    /**
     * Locale of pages displayed by PayPal payment experience.
     *
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->locale_code;
    }

}
