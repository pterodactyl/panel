<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class Error
 *
 * Details of an Error
 *
 * @package PayPal\Api
 *
 * @property string                     name
 * @property string                     message
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\ErrorDetails[] details
 * @property string                     information_link
 * @property string                     debug_id
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[]        links
 */
class Error extends PayPalModel
{
    /**
     * Human readable, unique name of the error.
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
     * Human readable, unique name of the error.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Reference ID of the purchase_unit associated with this error
     *
     * @deprecated Not publicly available
     * @param string $purchase_unit_reference_id
     *
     * @return $this
     */
    public function setPurchaseUnitReferenceId($purchase_unit_reference_id)
    {
        $this->purchase_unit_reference_id = $purchase_unit_reference_id;
        return $this;
    }

    /**
     * Reference ID of the purchase_unit associated with this error
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getPurchaseUnitReferenceId()
    {
        return $this->purchase_unit_reference_id;
    }

    /**
     * Message describing the error.
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Message describing the error.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * PayPal internal error code.
     *
     * @deprecated Not publicly available
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
     * PayPal internal error code.
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Additional details of the error
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ErrorDetails[] $details
     *
     * @return $this
     */
    public function setDetails($details)
    {
        $this->details = $details;
        return $this;
    }

    /**
     * Additional details of the error
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\ErrorDetails[]
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Append Details to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ErrorDetails $errorDetails
     * @return $this
     */
    public function addDetail($errorDetails)
    {
        if (!$this->getDetails()) {
            return $this->setDetails(array($errorDetails));
        } else {
            return $this->setDetails(
                array_merge($this->getDetails(), array($errorDetails))
            );
        }
    }

    /**
     * Remove Details from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ErrorDetails $errorDetails
     * @return $this
     */
    public function removeDetail($errorDetails)
    {
        return $this->setDetails(
            array_diff($this->getDetails(), array($errorDetails))
        );
    }

    /**
     * response codes returned from a payment processor such as avs, cvv, etc. Only supported when the `payment_method` is set to `credit_card`.
     *
     * @deprecated Not publicly available
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ProcessorResponse $processor_response
     *
     * @return $this
     */
    public function setProcessorResponse($processor_response)
    {
        $this->processor_response = $processor_response;
        return $this;
    }

    /**
     * response codes returned from a payment processor such as avs, cvv, etc. Only supported when the `payment_method` is set to `credit_card`.
     *
     * @deprecated Not publicly available
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\ProcessorResponse
     */
    public function getProcessorResponse()
    {
        return $this->processor_response;
    }

    /**
     * Fraud filter details.  Only supported when the `payment_method` is set to `credit_card`
     *
     * @deprecated Not publicly available
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\FmfDetails $fmf_details
     *
     * @return $this
     */
    public function setFmfDetails($fmf_details)
    {
        $this->fmf_details = $fmf_details;
        return $this;
    }

    /**
     * Fraud filter details.  Only supported when the `payment_method` is set to `credit_card`
     *
     * @deprecated Not publicly available
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\FmfDetails
     */
    public function getFmfDetails()
    {
        return $this->fmf_details;
    }

    /**
     * URI for detailed information related to this error for the developer.
     *
     * @param string $information_link
     *
     * @return $this
     */
    public function setInformationLink($information_link)
    {
        $this->information_link = $information_link;
        return $this;
    }

    /**
     * URI for detailed information related to this error for the developer.
     *
     * @return string
     */
    public function getInformationLink()
    {
        return $this->information_link;
    }

    /**
     * PayPal internal identifier used for correlation purposes.
     *
     * @param string $debug_id
     *
     * @return $this
     */
    public function setDebugId($debug_id)
    {
        $this->debug_id = $debug_id;
        return $this;
    }

    /**
     * PayPal internal identifier used for correlation purposes.
     *
     * @return string
     */
    public function getDebugId()
    {
        return $this->debug_id;
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
