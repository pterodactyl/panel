<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class Image
 *
 * @package PayPal\Api
 *
 * @property string image
 */
class Image extends PayPalModel
{
    /**
     * List of invoices belonging to a merchant.
     *
     * @param string $imageBase64String
     *
     * @return $this
     */
    public function setImage($imageBase64String)
    {
        $this->image = $imageBase64String;
        return $this;
    }

    /**
     * Get Image as Base-64 encoded String
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Stores the Image to file
     *
     * @param string $name File Name
     * @return string File name
     */
    public function saveToFile($name = null)
    {
        // Self Generate File Location
        if (!$name) {
            $name = uniqid() . '.png';
        }
        // Save to File
        file_put_contents($name, base64_decode($this->getImage()));
        return $name;
    }

}
