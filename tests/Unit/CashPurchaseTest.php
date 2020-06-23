<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;
use IFRS\Models\Currency;

use IFRS\Transactions\CashPurchase;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;
use IFRS\Models\Vat;

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

        $cashPurchase = new CashPurchase([
            "account_id" => $bankAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $cashPurchase->save();

        $this->assertEquals($cashPurchase->account->name, $bankAccount->name);
        $this->assertEquals($cashPurchase->account->description, $bankAccount->description);
        $this->assertEquals($cashPurchase->transaction_no, "CP0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting CashPurchase Transaction
     *
     * @return void
     */
    public function testPostCashPurchaseTransaction()
    {
        $cashPurchase = new CashPurchase([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_EXPENSE
            ])->id,
        ]);
        $cashPurchase->addLineItem($lineItem);

        $cashPurchase->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->folio_account, $cashPurchase->account->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $cashPurchase->account->id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->folio_account, $cashPurchase->account->id);
        $this->assertEquals($vat_debit->post_account, $lineItem->vat->account_id);
        $this->assertEquals($vat_credit->post_account, $cashPurchase->account->id);
        $this->assertEquals($vat_credit->folio_account, $lineItem->vat->account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($cashPurchase->amount, 116);
    }

    /**
     * Test Cash Purchase Line Item Account.
     *
     * @return void
     */
    public function testCashPurchaseLineItemAccount()
    {
        $cashPurchase = new CashPurchase([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage(
            "Cash Purchase LineItem Account must be of type "
                . "Operating Expense, Direct Expense, Overhead Expense, "
                . "Other Expense, Non Current Asset, Current Asset, Inventory"
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
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
        $cashPurchase = new CashPurchase([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Cash Purchase Main Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_EXPENSE
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
        $transaction = new CashPurchase([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $found = CashPurchase::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
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
        $transaction = new CashPurchase([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction2 = new CashPurchase([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now()->addWeeks(2),
            "narration" => $this->faker->word,
        ]);
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
