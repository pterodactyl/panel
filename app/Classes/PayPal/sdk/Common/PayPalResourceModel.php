<?php

namespace Pterodactyl\Classes\PayPal\sdk\Common;

use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Rest\IResource;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;

/**
 * Class PayPalResourceModel
 * An Executable PayPalModel Class
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 * @package PayPal\Common
 */
class PayPalResourceModel extends PayPalModel implements IResource
{

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

    public function getLink($rel)
    {
        foreach ($this->links as $link) {
            if ($link->getRel() == $rel) {
                return $link->getHref();
            }
        }
        return null;
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


    /**
     * Execute SDK Call to Paypal services
     *
     * @param string      $url
     * @param string      $method
     * @param string      $payLoad
     * @param array $headers
     * @param ApiContext      $apiContext
     * @param PayPalRestCall      $restCall
     * @param array $handlers
     * @return string json response of the object
     */
    protected static function executeCall($url, $method, $payLoad, $headers = array(), $apiContext = null, $restCall = null, $handlers = array('Pterodactyl\Classes\PayPal\sdk\Handler\RestHandler'))
    {
        //Initialize the context and rest call object if not provided explicitly
        $apiContext = $apiContext ? $apiContext : new ApiContext(self::$credential);
        $restCall = $restCall ? $restCall : new PayPalRestCall($apiContext);

        //Make the execution call
        $json = $restCall->execute($handlers, $url, $method, $payLoad, $headers);
        return $json;
    }
}
