<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class BankAccountsList
 *
 * A list of Bank Account Resources
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\BankAccount[] bank_accounts
 * @property int count
 * @property string next_id
 */
class BankAccountsList extends PayPalModel
{
    /**
     * A list of bank account resources
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\BankAccount[] $bank_accounts
     *
     * @return $this
     */
    public function setBankAccounts($bank_accounts)
    {
        $this->{"bank-accounts"} = $bank_accounts;
        return $this;
    }

    /**
     * A list of bank account resources
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\BankAccount[]
     */
    public function getBankAccounts()
    {
        return $this->{"bank-accounts"};
    }

    /**
     * Append BankAccounts to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\BankAccount $bankAccount
     * @return $this
     */
    public function addBankAccount($bankAccount)
    {
        if (!$this->getBankAccounts()) {
            return $this->setBankAccounts(array($bankAccount));
        } else {
            return $this->setBankAccounts(
                array_merge($this->getBankAccounts(), array($bankAccount))
            );
        }
    }

    /**
     * Remove BankAccounts from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\BankAccount $bankAccount
     * @return $this
     */
    public function removeBankAccount($bankAccount)
    {
        return $this->setBankAccounts(
            array_diff($this->getBankAccounts(), array($bankAccount))
        );
    }

    /**
     * Number of items returned in each range of results. Note that the last results range could have fewer items than the requested number of items.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Number of items returned in each range of results. Note that the last results range could have fewer items than the requested number of items.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Identifier of the next element to get the next range of results.
     *
     * @param string $next_id
     *
     * @return $this
     */
    public function setNextId($next_id)
    {
        $this->next_id = $next_id;
        return $this;
    }

    /**
     * Identifier of the next element to get the next range of results.
     *
     * @return string
     */
    public function getNextId()
    {
        return $this->next_id;
    }

}
