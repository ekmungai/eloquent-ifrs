<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Ledger;
use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\Currency;

use Ekmungai\IFRS\Transactions\CashPurchase;

use Ekmungai\IFRS\Exceptions\LineItemAccount;
use Ekmungai\IFRS\Exceptions\MainAccount;

class CashPurchaseTest extends TestCase
{
    /**
     * Test Creating CashPurchase Transaction
     *
     * @return void
     */
    public function testCreateCashPurchaseTransaction()
    {
        $bankAccount = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);

        $cashPurchase = CashPurchase::new($bankAccount, Carbon::now(), $this->faker->word);
        $cashPurchase->setDate(Carbon::now());
        $cashPurchase->save();

        $this->assertEquals($cashPurchase->getAccount()->name, $bankAccount->name);
        $this->assertEquals($cashPurchase->getAccount()->description, $bankAccount->description);
        $this->assertEquals($cashPurchase->getTransactionNo(), "CP0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting CashPurchase Transaction
     *
     * @return void
     */
    public function testPostCashPurchaseTransaction()
    {
        $cashPurchase = CashPurchase::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::OPERATING_EXPENSE
            ])->id,
        ]);
        $cashPurchase->addLineItem($lineItem);

        $cashPurchase->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->folio_account, $cashPurchase->getAccount()->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $cashPurchase->getAccount()->id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->folio_account, $cashPurchase->getAccount()->id);
        $this->assertEquals($vat_debit->post_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_credit->post_account, $cashPurchase->getAccount()->id);
        $this->assertEquals($vat_credit->folio_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($cashPurchase->getAmount(), 116);
    }

    /**
     * Test Cash Purchase Line Item Account.
     *
     * @return void
     */
    public function testCashPurchaseLineItemAccount()
    {
        $cashPurchase = CashPurchase::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage(
            "Cash Purchase LineItem Account must be of type "
            ."Operating Expense, Direct Expense, Overhead Expense, "
            ."Other Expense, Non Current Asset, Current Asset, Inventory"
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $cashPurchase->addLineItem($lineItem);

        $cashPurchase->post();
    }

    /**
     * Cash Purchase Test Main Account.
     *
     * @return void
     */
    public function testCashPurchaseMainAccount()
    {
        $cashPurchase = CashPurchase::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Cash Purchase Main Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $cashPurchase->addLineItem($lineItem);

        $cashPurchase->post();
    }

    /**
     * Test Cash Purchase Find.
     *
     * @return void
     */
    public function testCashPurchaseFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction = CashPurchase::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = CashPurchase::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
    }

    /**
     * Test Cash Purchase Fetch.
     *
     * @return void
     */
    public function testCashPurchaseFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction = CashPurchase::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction2 = CashPurchase::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(CashPurchase::fetch()), 2);
        $this->assertEquals(count(CashPurchase::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(CashPurchase::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(CashPurchase::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(CashPurchase::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $this->assertEquals(count(CashPurchase::fetch(null, null, $account)), 1);
        $this->assertEquals(count(CashPurchase::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(CashPurchase::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(CashPurchase::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(CashPurchase::fetch(null, null, null, $currency)), 0);
    }
}
