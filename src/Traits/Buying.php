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

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;

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
     * Validate Buying Transaction LineItems.
     */
    public function post(): void
    {
        parent::save();

        $purchasable = [
            Account::OPERATING_EXPENSE,
            Account::DIRECT_EXPENSE,
            Account::OVERHEAD_EXPENSE,
            Account::OTHER_EXPENSE,
            Account::NON_CURRENT_ASSET,
            Account::CURRENT_ASSET,
            Account::INVENTORY
        ];

        foreach ($this->getLineItems() as $lineItem) {
            if (!in_array($lineItem->account->account_type, $purchasable)) {
                throw new LineItemAccount(self::PREFIX, $purchasable);
            }
        }

        parent::post();
    }
}
