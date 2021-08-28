<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Traits;

use IFRS\Models\Account;
use IFRS\Models\LineItem;

use IFRS\Exceptions\MainAccount;
use IFRS\Exceptions\LineItemAccount;

trait Buying
{
    /**
     * Validate Buying Transaction Main Account.
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) or $this->account->account_type != Account::PAYABLE) {
            throw new MainAccount(self::PREFIX, Account::PAYABLE);
        }

        return parent::save();
    }

    /**
     * Validate Buying Transaction LineItem.
     */
    public function addLineItem(LineItem $lineItem): bool
    {

        if (!in_array($lineItem->account->account_type, Account::PURCHASABLES)) {
            throw new LineItemAccount(self::PREFIX, Account::PURCHASABLES);
        }

        return parent::addLineItem($lineItem);
    }
}
