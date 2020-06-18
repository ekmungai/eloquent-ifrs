<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\LineItem;
use IFRS\Models\Ledger;
use IFRS\Models\Vat;
use IFRS\Transactions\JournalEntry;

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

        $journalEntry = new JournalEntry([
            "account_id" => $mainAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $journalEntry->save();

        $this->assertEquals($journalEntry->account->name, $mainAccount->name);
        $this->assertEquals($journalEntry->account->description, $mainAccount->description);
        $this->assertEquals($journalEntry->transaction_no, "JN0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting Journal Entry Transaction
     *
     * @return void
     */
    public function testPostJournalEntryTransaction()
    {
        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create()->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
        ]);
        $journalEntry->addLineItem($lineItem);

        $journalEntry->post();

        $ledgers = Ledger::where("transaction_id", $journalEntry->id)->get();
        $debit = $ledgers->where("entry_type", Balance::DEBIT)->first();
        $credit = $ledgers->where("entry_type", Balance::CREDIT)->first();

        $this->assertEquals($debit->folio_account, $journalEntry->account->id);
        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $journalEntry->account->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $this->assertEquals($journalEntry->getAmount(), 100);

        $journalEntry2 = new JournalEntry([
            "account_id" => factory(Account::class)->create()->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "amount" => 50,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
        ]);
        $lineItem2 = factory(LineItem::class)->create([
            "amount" => 25,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
        ]);
        $journalEntry2->addLineItem($lineItem1);
        $journalEntry2->addLineItem($lineItem2);

        $journalEntry2->post();

        $ledgers = Ledger::where("transaction_id", $journalEntry2->id)->get();

        $debits = $ledgers->where("entry_type", Balance::DEBIT);
        $credits = $ledgers->where("entry_type", Balance::CREDIT);

        $debit1 = $debits->where("amount", 50)->first();
        $credit1 = $credits->where("amount", 50)->first();

        $debit2 = $debits->where("amount", 25)->first();
        $credit2 = $credits->where("amount", 25)->first();

        $debit3 = $debits->where("amount", 4)->first();
        $credit3 = $credits->where("amount", 4)->first();

        // lineItem 1
        $this->assertEquals($debit1->post_account, $journalEntry2->account->id);
        $this->assertEquals($debit1->folio_account, $lineItem1->account_id);
        $this->assertEquals($credit1->folio_account, $journalEntry2->account->id);
        $this->assertEquals($credit1->post_account, $lineItem1->account_id);

        // lineItem 2
        $this->assertEquals($debit2->post_account, $journalEntry2->account->id);
        $this->assertEquals($debit2->folio_account, $lineItem2->account_id);
        $this->assertEquals($credit2->folio_account, $journalEntry2->account->id);
        $this->assertEquals($credit2->post_account, $lineItem2->account_id);

        // lineItem 2 Vat
        $this->assertEquals($debit3->post_account, $journalEntry2->account->id);
        $this->assertEquals($debit3->folio_account, $lineItem2->vat->account_id);
        $this->assertEquals($credit3->folio_account, $journalEntry2->account->id);
        $this->assertEquals($credit3->post_account, $lineItem2->vat->account_id);

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
        $transaction = new JournalEntry([
            "account_id" => $account,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $found = JournalEntry::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
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
        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();


        $account2 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction2 = new JournalEntry([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now()->addWeeks(2),
            "narration" => $this->faker->word,
        ]);
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
