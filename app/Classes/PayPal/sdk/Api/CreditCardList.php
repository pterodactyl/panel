<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;

/**
 * Class CreditCardList
 *
 * A list of Credit Card Resources
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\CreditCard[] items
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 * @property int total_items
 * @property int total_pages
 */
class CreditCardList extends PayPalResourceModel
{
    /**
     * A list of credit card resources
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\CreditCard[] $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * A list of credit card resources
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\CreditCard[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Append Items to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\CreditCard $creditCard
     * @return $this
     */
    public function addItem($creditCard)
    {
        if (!$this->getItems()) {
            return $this->setItems(array($creditCard));
        } else {
            return $this->setItems(
                array_merge($this->getItems(), array($creditCard))
            );
        }
    }

    /**
     * Remove Items from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\CreditCard $creditCard
     * @return $this
     */
    public function removeItem($creditCard)
    {
        return $this->setItems(
            array_diff($this->getItems(), array($creditCard))
        );
    }

    /**
     * Total number of items present in the given list. Note that the number of items might be larger than the records in the current page.
     *
     * @param int $total_items
     *
     * @return $this
     */
    public function setTotalItems($total_items)
    {
        $this->total_items = $total_items;
        return $this;
    }

    /**
     * Total number of items present in the given list. Note that the number of items might be larger than the records in the current page.
     *
     * @return int
     */
    public function getTotalItems()
    {
        return $this->total_items;
    }

    /**
     * Total number of pages that exist, for the total number of items, with the given page size.
     *
     * @param int $total_pages
     *
     * @return $this
     */
    public function setTotalPages($total_pages)
    {
        $this->total_pages = $total_pages;
        return $this;
    }

    /**
     * Total number of pages that exist, for the total number of items, with the given page size.
     *
     * @return int
     */
    public function getTotalPages()
    {
        return $this->total_pages;
    }

}
