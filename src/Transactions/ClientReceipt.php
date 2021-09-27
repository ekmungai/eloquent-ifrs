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

use IFRS\Interfaces\Assignable;


use IFRS\Traits\Assigning;

use IFRS\Models\Account;
use IFRS\Models\LineItem;
use IFRS\Models\Transaction;

class ClientReceipt extends Transaction implements Assignable
{
    use Assigning;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::RC;

    /**
     * Construct new ClientReceipt
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Validate ClientReceipt Main Account
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) || $this->account->account_type != Account::RECEIVABLE) {
            throw new MainAccount(self::PREFIX, Account::RECEIVABLE);
        }

        return parent::save();
    }

    /**
     * Validate Client Receipt LineItem.
     */
    public function addLineItem(LineItem $lineItem): bool
    {
        if ($lineItem->account->account_type != Account::BANK) {
            throw new LineItemAccount(self::PREFIX, [Account::BANK]);
        }

        if ($lineItem->vat['total'] > 0) {
            throw new VatCharge(self::PREFIX);
        }

        return parent::addLineItem($lineItem);
    }
}
