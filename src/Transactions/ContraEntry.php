<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Transactions;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;
use IFRS\Exceptions\VatCharge;
use IFRS\Interfaces\Fetchable;
use IFRS\Models\Account;
use IFRS\Models\LineItem;
use IFRS\Models\Transaction;
use IFRS\Traits\Fetching;

class ContraEntry extends Transaction implements Fetchable
{
    use Fetching;

    use \Parental\HasParent;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CE;

    /**
     * Construct new ContraEntry
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = false;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Validate ContraEntry Main Account
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) || $this->account->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        return parent::save();
    }

    /**
     * Validate ContraEntry LineItem
     */
    public function addLineItem(LineItem $lineItem): void
    {
        if ($lineItem->account->account_type != Account::BANK) {
            throw new LineItemAccount(self::PREFIX, [Account::BANK]);
        }

        if ($lineItem->vat->rate > 0) {
            throw new VatCharge(self::PREFIX);
        }

        parent::addLineItem($lineItem);
    }
}
