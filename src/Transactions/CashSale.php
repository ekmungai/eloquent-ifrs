<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Transactions;

use IFRS\Interfaces\Fetchable;
use IFRS\Interfaces\Sells;

use IFRS\Traits\Fetching;
use IFRS\Traits\Selling;

use IFRS\Models\Account;
use IFRS\Models\Transaction;

use IFRS\Exceptions\MainAccount;

class CashSale extends Transaction implements Sells, Fetchable
{
    use Selling;
    use Fetching;

    use \Parental\HasParent;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CS;

    /**
     * Construct new CashSale
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
     * Validate CashSale Main Account
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) || $this->account->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        return parent::save();
    }
}
