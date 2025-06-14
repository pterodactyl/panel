<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PlanList
 *
 * Resource representing a list of billing plans with basic information and get link.
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Plan[] plans
 * @property string total_items
 * @property string total_pages
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[] links
 */
class PlanList extends PayPalModel
{
    /**
     * Array of billing plans.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Plan[] $plans
     *
     * @return $this
     */
    public function setPlans($plans)
    {
        $this->plans = $plans;
        return $this;
    }

    /**
     * Array of billing plans.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Plan[]
     */
    public function getPlans()
    {
        return $this->plans;
    }

    /**
     * Append Plans to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Plan $plan
     * @return $this
     */
    public function addPlan($plan)
    {
        if (!$this->getPlans()) {
            return $this->setPlans(array($plan));
        } else {
            return $this->setPlans(
                array_merge($this->getPlans(), array($plan))
            );
        }
    }

    /**
     * Remove Plans from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Plan $plan
     * @return $this
     */
    public function removePlan($plan)
    {
        return $this->setPlans(
            array_diff($this->getPlans(), array($plan))
        );
    }

    /**
     * Total number of items.
     *
     * @param string $total_items
     *
     * @return $this
     */
    public function setTotalItems($total_items)
    {
        $this->total_items = $total_items;
        return $this;
    }

    /**
     * Total number of items.
     *
     * @return string
     */
    public function getTotalItems()
    {
        return $this->total_items;
    }

    /**
     * Total number of pages.
     *
     * @param string $total_pages
     *
     * @return $this
     */
    public function setTotalPages($total_pages)
    {
        $this->total_pages = $total_pages;
        return $this;
    }

    /**
     * Total number of pages.
     *
     * @return string
     */
    public function getTotalPages()
    {
        return $this->total_pages;
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
