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

use IFRS\Exceptions\MainAccount;
use IFRS\Exceptions\LineItemAccount;

trait Selling
{
    /**
     * Validate Selling Transaction Main Account.
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) or $this->account->account_type != Account::RECEIVABLE) {
            throw new MainAccount(self::PREFIX, Account::RECEIVABLE);
        }

        return parent::save();
    }

    /**
     * Validate Selling Transaction LineItems.
     */
    public function post(): void
    {
        parent::save();

        foreach ($this->getLineItems() as $lineItem) {
            if ($lineItem->account->account_type != Account::OPERATING_REVENUE) {
                throw new LineItemAccount(self::PREFIX, [Account::OPERATING_REVENUE]);
            }
        }

        parent::post();
    }
}
