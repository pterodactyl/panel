<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Validation\UrlValidator;

/**
 * Class FlowConfig
 *
 * Parameters for flow configuration.
 *
 * @package PayPal\Api
 *
 * @property string landing_page_type
 * @property string bank_txn_pending_url
 */
class FlowConfig extends PayPalModel
{
    /**
     * Type of PayPal page to be displayed when a user lands on the PayPal site for checkout. Allowed values: `Billing` or `Login`. When set to `Billing`, the Non-PayPal account landing page is used. When set to `Login`, the PayPal account login landing page is used.
     *
     *
     * @param string $landing_page_type
     *
     * @return $this
     */
    public function setLandingPageType($landing_page_type)
    {
        $this->landing_page_type = $landing_page_type;
        return $this;
    }

    /**
     * Type of PayPal page to be displayed when a user lands on the PayPal site for checkout. Allowed values: `Billing` or `Login`. When set to `Billing`, the Non-PayPal account landing page is used. When set to `Login`, the PayPal account login landing page is used.
     *
     * @return string
     */
    public function getLandingPageType()
    {
        return $this->landing_page_type;
    }

    /**
     * The URL on the merchant site for transferring to after a bank transfer payment.
     *
     *
     * @param string $bank_txn_pending_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setBankTxnPendingUrl($bank_txn_pending_url)
    {
        UrlValidator::validate($bank_txn_pending_url, "BankTxnPendingUrl");
        $this->bank_txn_pending_url = $bank_txn_pending_url;
        return $this;
    }

    /**
     * The URL on the merchant site for transferring to after a bank transfer payment.
     *
     * @return string
     */
    public function getBankTxnPendingUrl()
    {
        return $this->bank_txn_pending_url;
    }

}
