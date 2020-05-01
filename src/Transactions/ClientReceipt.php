<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Transactions;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Transaction;

use Ekmungai\IFRS\Interfaces\Fetchable;

use Ekmungai\IFRS\Traits\Fetching;

use Ekmungai\IFRS\Exceptions\MainAccount;
use Ekmungai\IFRS\Exceptions\LineItemAccount;
use Ekmungai\IFRS\Exceptions\VatCharge;
use Ekmungai\IFRS\Interfaces\Assignable;
use Ekmungai\IFRS\Traits\Assigning;

class ClientReceipt extends Transaction implements Fetchable, Assignable
{
    use Fetching;
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
     *
     */
    public function __construct($attributes = []) {

        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Validate ClientReceipt Main Account
     */
    public function save(): void
    {
        if (is_null($this->getAccount()) or $this->getAccount()->account_type != Account::RECEIVABLE) {
            throw new MainAccount(self::PREFIX, Account::RECEIVABLE);
        }

        parent::save();
    }

    /**
     * Validate ClientReceipt LineItems
     */
    public function post(): void
    {
        $this->save();

        foreach ($this->getLineItems() as $lineItem) {
            if ($lineItem->account->account_type != Account::BANK) {
                throw new LineItemAccount(self::PREFIX, [Account::BANK]);
            }

            if ($lineItem->vat->rate > 0) {
                throw new VatCharge(self::PREFIX);
            }
        }

        parent::post();
    }
}
