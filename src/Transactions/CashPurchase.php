<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Transactions;

use Ekmungai\IFRS\Interfaces\Fetchable;
use Ekmungai\IFRS\Interfaces\Buys;

use Ekmungai\IFRS\Traits\Buying;
use Ekmungai\IFRS\Traits\Fetching;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Transaction;

use Ekmungai\IFRS\Exceptions\MainAccount;

class CashPurchase extends Transaction implements Buys, Fetchable
{
    use Buying;
    use Fetching;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CP;

    /**
     * Construct new CashPurchase
     *
     * @param array $attributes
     *
     */
    public function __construct($attributes = []) {

        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Validate CashPurchase Main Account
     */
    public function save(): void
    {
        if (is_null($this->getAccount()) or $this->getAccount()->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        parent::save();
    }
}
