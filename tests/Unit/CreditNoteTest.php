<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\Ledger;
use Ekmungai\IFRS\Models\LineItem;

use Ekmungai\IFRS\Transactions\CreditNote;

use Ekmungai\IFRS\Exceptions\LineItemAccount;
use Ekmungai\IFRS\Exceptions\MainAccount;

class CreditNoteTest extends TestCase
{
    /**
     * Test Creating CreditNote Transaction
     *
     * @return void
     */
    public function testCreateCreditNoteTransaction()
    {
        $clientAccount = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);

        $creditNote = CreditNote::new($clientAccount, Carbon::now(), $this->faker->word);
        $creditNote->save();

        $this->assertEquals($creditNote->getAccount()->name, $clientAccount->name);
        $this->assertEquals($creditNote->getAccount()->description, $clientAccount->description);
        $this->assertEquals($creditNote->getTransactionNo(), "CN0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting CreditNote Transaction
     *
     * @return void
     */
    public function testPostCreditNoteTransaction()
    {
        $creditNote = CreditNote::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECEIVABLE,
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
                "account_type" => Account::OPERATING_REVENUE
            ])->id,
        ]);
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        $debit = Ledger::where("entry_type", Balance::D)->get()[0];
        $credit = Ledger::where("entry_type", Balance::C)->get()[0];

        $this->assertEquals($debit->folio_account, $creditNote->getAccount()->id);
        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $creditNote->getAccount()->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::D)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::C)->get()[1];

        $this->assertEquals($vat_debit->folio_account, $creditNote->getAccount()->id);
        $this->assertEquals($vat_debit->post_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_credit->post_account, $creditNote->getAccount()->id);
        $this->assertEquals($vat_credit->folio_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($creditNote->getAmount(), 116);
    }

    /**
     * Test Credit Note Line Item Account.
     *
     * @return void
     */
    public function testCreditNoteLineItemAccount()
    {
        $creditNote = CreditNote::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Credit Note LineItem Account must be of type Operating Revenue');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $creditNote->addLineItem($lineItem);

        $creditNote->post();
    }

    /**
     * Test Credit Note Main Account.
     *
     * @return void
     */
    public function testCreditNoteMainAccount()
    {
        $creditNote = CreditNote::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Credit Note Main Account must be of type Receivable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $creditNote->addLineItem($lineItem);

        $creditNote->post();
    }

    /**
     * Test Credit Note Find.
     *
     * @return void
     */
    public function testCreditNoteFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction = CreditNote::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = CreditNote::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
    }

    /**
     * Test Credit Note Fetch.
     *
     * @return void
     */
    public function testCreditNoteFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction = CreditNote::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction2 = CreditNote::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(CreditNote::fetch()), 2);
        $this->assertEquals(count(CreditNote::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(CreditNote::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(CreditNote::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(CreditNote::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $this->assertEquals(count(CreditNote::fetch(null, null, $account)), 1);
        $this->assertEquals(count(CreditNote::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(CreditNote::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(CreditNote::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(CreditNote::fetch(null, null, null, $currency)), 0);
    }
}
