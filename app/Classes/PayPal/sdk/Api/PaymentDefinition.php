<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PaymentDefinition
 *
 * Resource representing payment definition scheduling information.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string name
 * @property string type
 * @property string frequency_interval
 * @property string frequency
 * @property string cycles
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency amount
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\ChargeModel[] charge_models
 */
class PaymentDefinition extends PayPalModel
{
    /**
     * Identifier of the payment_definition. 128 characters max.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Identifier of the payment_definition. 128 characters max.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Name of the payment definition. 128 characters max.
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
     * Name of the payment definition. 128 characters max.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Type of the payment definition. Allowed values: `TRIAL`, `REGULAR`.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Type of the payment definition. Allowed values: `TRIAL`, `REGULAR`.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * How frequently the customer should be charged.
     *
     * @param string $frequency_interval
     *
     * @return $this
     */
    public function setFrequencyInterval($frequency_interval)
    {
        $this->frequency_interval = $frequency_interval;
        return $this;
    }

    /**
     * How frequently the customer should be charged.
     *
     * @return string
     */
    public function getFrequencyInterval()
    {
        return $this->frequency_interval;
    }

    /**
     * Frequency of the payment definition offered. Allowed values: `WEEK`, `DAY`, `YEAR`, `MONTH`.
     *
     * @param string $frequency
     *
     * @return $this
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;
        return $this;
    }

    /**
     * Frequency of the payment definition offered. Allowed values: `WEEK`, `DAY`, `YEAR`, `MONTH`.
     *
     * @return string
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * Number of cycles in this payment definition.
     *
     * @param string $cycles
     *
     * @return $this
     */
    public function setCycles($cycles)
    {
        $this->cycles = $cycles;
        return $this;
    }

    /**
     * Number of cycles in this payment definition.
     *
     * @return string
     */
    public function getCycles()
    {
        return $this->cycles;
    }

    /**
     * Amount that will be charged at the end of each cycle for this payment definition.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Amount that will be charged at the end of each cycle for this payment definition.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Array of charge_models for this payment definition.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ChargeModel[] $charge_models
     *
     * @return $this
     */
    public function setChargeModels($charge_models)
    {
        $this->charge_models = $charge_models;
        return $this;
    }

    /**
     * Array of charge_models for this payment definition.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\ChargeModel[]
     */
    public function getChargeModels()
    {
        return $this->charge_models;
    }

    /**
     * Append ChargeModels to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ChargeModel $chargeModel
     * @return $this
     */
    public function addChargeModel($chargeModel)
    {
        if (!$this->getChargeModels()) {
            return $this->setChargeModels(array($chargeModel));
        } else {
            return $this->setChargeModels(
                array_merge($this->getChargeModels(), array($chargeModel))
            );
        }
    }

    /**
     * Remove ChargeModels from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\ChargeModel $chargeModel
     * @return $this
     */
    public function removeChargeModel($chargeModel)
    {
        return $this->setChargeModels(
            array_diff($this->getChargeModels(), array($chargeModel))
        );
    }

}
