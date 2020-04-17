<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Tests\TestCase;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Currency;
use App\Models\LineItem;
use App\Models\Ledger;

use App\Transactions\JournalEntry;

class JournalEntryTest extends TestCase
{
    /**
     * Test Creating JournalEntry Transaction
     *
     * @return void
     */
    public function testCreateJournalEntryTransaction()
    {
        $mainAccount = factory(Account::class)->create();

        $journalEntry = JournalEntry::new($mainAccount, Carbon::now(), $this->faker->word);
        $journalEntry->save();

        $this->assertEquals($journalEntry->getAccount()->name, $mainAccount->name);
        $this->assertEquals($journalEntry->getAccount()->description, $mainAccount->description);
        $this->assertEquals($journalEntry->getTransactionNo(), "JN0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting Journal Entry Transaction
     *
     * @return void
     */
    public function testPostJournalEntryTransaction()
    {
        $journalEntry = JournalEntry::new(
            factory('App\Models\Account')->create(),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('App\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]);
        $journalEntry->addLineItem($lineItem);

        $journalEntry->post();

        $ledgers = Ledger::where("transaction_id", $journalEntry->getId())->get();
        $debit = $ledgers->where("entry_type", Balance::D)->first();
        $credit = $ledgers->where("entry_type", Balance::C)->first();

        $this->assertEquals($debit->folio_account, $journalEntry->getAccount()->id);
        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $journalEntry->getAccount()->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $this->assertEquals($journalEntry->getAmount(), 100);

        $journalEntry2 = JournalEntry::new(
            factory('App\Models\Account')->create(),
            Carbon::now(),
            $this->faker->word
        );
        $journalEntry2->setCredited(false);

        $lineItem1 = factory(LineItem::class)->create([
            "amount" => 50,
            "vat_id" => factory('App\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]);
        $lineItem2 = factory(LineItem::class)->create([
            "amount" => 25,
            "vat_id" => factory('App\Models\Vat')->create([
                "rate" => 16
            ])->id,
        ]);
        $journalEntry2->addLineItem($lineItem1);
        $journalEntry2->addLineItem($lineItem2);

        $journalEntry2->post();

        $ledgers = Ledger::where("transaction_id", $journalEntry2->getId())->get();

        $debits = $ledgers->where("entry_type", Balance::D);
        $credits = $ledgers->where("entry_type", Balance::C);

        $debit1 = $debits->where("amount", 50)->first();
        $credit1 = $credits->where("amount", 50)->first();

        $debit2 = $debits->where("amount", 25)->first();
        $credit2 = $credits->where("amount", 25)->first();

        $debit3 = $debits->where("amount", 4)->first();
        $credit3 = $credits->where("amount", 4)->first();

        // lineItem 1
        $this->assertEquals($debit1->post_account, $journalEntry2->getAccount()->id);
        $this->assertEquals($debit1->folio_account, $lineItem1->account_id);
        $this->assertEquals($credit1->folio_account, $journalEntry2->getAccount()->id);
        $this->assertEquals($credit1->post_account, $lineItem1->account_id);

        // lineItem 2
        $this->assertEquals($debit2->post_account, $journalEntry2->getAccount()->id);
        $this->assertEquals($debit2->folio_account, $lineItem2->account_id);
        $this->assertEquals($credit2->folio_account, $journalEntry2->getAccount()->id);
        $this->assertEquals($credit2->post_account, $lineItem2->account_id);

        // lineItem 2 Vat
        $this->assertEquals($debit3->post_account, $journalEntry2->getAccount()->id);
        $this->assertEquals($debit3->folio_account, $lineItem2->vat_account_id);
        $this->assertEquals($credit3->folio_account, $journalEntry2->getAccount()->id);
        $this->assertEquals($credit3->post_account, $lineItem2->vat_account_id);

        $this->assertEquals($journalEntry2->getAmount(), 79);
    }

    /**
     * Test Journal Entry Find.
     *
     * @return void
     */
    public function testJournalEntryFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction = JournalEntry::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = JournalEntry::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
    }

    /**
     * Test Journal Entry Fetch.
     *
     * @return void
     */
    public function testJournalEntryFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction = JournalEntry::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction2 = JournalEntry::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(JournalEntry::fetch()), 2);
        $this->assertEquals(count(JournalEntry::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(JournalEntry::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(JournalEntry::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(JournalEntry::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $this->assertEquals(count(JournalEntry::fetch(null, null, $account)), 1);
        $this->assertEquals(count(JournalEntry::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(JournalEntry::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(JournalEntry::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(JournalEntry::fetch(null, null, null, $currency)), 0);
    }
}
