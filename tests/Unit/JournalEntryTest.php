<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\LineItem;
use IFRS\Models\Ledger;
use IFRS\Models\Transaction;
use IFRS\Models\Vat;

use IFRS\Transactions\JournalEntry;

use IFRS\Exceptions\UnbalancedTransaction;
use IFRS\Exceptions\InvalidVatRate;
use IFRS\Exceptions\MissingMainAccountAmount;
use IFRS\Exceptions\MultipleVatError;

class JournalEntryTest extends TestCase
{
    /**
     * Test Creating JournalEntry Transaction
     *
     * @return void
     */
    public function testCreateJournalEntryTransaction()
    {
        $mainAccount = factory(Account::class)->create([
            'category_id' => null
        ]);

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
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "quantity" => 1,
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

        $this->assertEquals($journalEntry->amount, 100);

        $journalEntry2 = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "amount" => 50,
            "quantity" => 1,
        ]);
        $lineItem2 = factory(LineItem::class)->create([
            "amount" => 25,
            "quantity" => 1,
        ]);
        $lineItem2->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem2->save();
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
        $this->assertEquals($debit3->folio_account, $lineItem2->appliedVats[0]->vat->account_id);
        $this->assertEquals($credit3->folio_account, $journalEntry2->account->id);
        $this->assertEquals($credit3->post_account, $lineItem2->appliedVats[0]->vat->account_id);

        $this->assertEquals($journalEntry2->amount, 79);
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
            'category_id' => null
        ]);
        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);
        $transaction->save();

        $found = JournalEntry::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
    }

    /**
     * Test Compound Journal Entry Transaction
     *
     * @return void
     */
    public function testCompoundJournalEntryTransaction()
    {

        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "compound" => true,
            "main_account_amount" => 10
        ]);

        $journalEntry->save();
        
        $lineItem1 = factory(LineItem::class)->create([
            "amount" => 30,
            "quantity" => 1,
            "credited" => true,
        ]);

        $lineItem2 = factory(LineItem::class)->create([
            "amount" => 25,
            "quantity" => 1,
        ]);

        $lineItem3 = factory(LineItem::class)->create([
            "amount" => 15,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem1);
        $journalEntry->addLineItem($lineItem2);
        $journalEntry->addLineItem($lineItem3);

        $this->assertEquals($journalEntry->amount, 40);
        $this->assertEquals($journalEntry->getCompoundEntries(), [
            "C" => [
                $lineItem1->account_id => 30,
                $journalEntry->account_id => 10.0
            ],
            "D" => [
                $lineItem2->account_id => 25,
                $lineItem3->account_id => 15
            ]
        ]);

        $journalEntry->post();
        
        $transaction = Transaction::find($journalEntry->id);
        
        $this->assertEquals($transaction->getCompoundEntries(), [
            "C" => [
                $lineItem1->account_id => 30,
                $journalEntry->account_id => 10.0
            ],
            "D" => [
                $lineItem2->account_id => 25,
                $lineItem3->account_id => 15
            ]
        ]);

        // Main Account
        $this->assertEquals(Ledger::contribution($transaction->account, $transaction->id), -10);

        // lineItem 1
        $this->assertEquals(Ledger::contribution($lineItem1->account, $transaction->id), -30);

        // lineItem 2
        $this->assertEquals(Ledger::contribution($lineItem2->account, $transaction->id), 25);
        
        // lineItem 3
        $this->assertEquals(Ledger::contribution($lineItem3->account, $transaction->id), 15);

        $this->assertEquals($transaction->amount, 40);
    }

    /**
     * Test Unbalanced Journal Entry Exception
     *
     * @return void
     */
    public function testUnbalancedJournalEntryException()
    {
        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "compound" => true,
            "main_account_amount" => 10
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "amount" => 30,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem1);

        $this->expectException(UnbalancedTransaction::class);
        $this->expectExceptionMessage('Total Debit amounts do not match total Credit amounts ');

        $journalEntry->post();
    }

    /**
     * Test Invalid Vat Rate Exception
     *
     * @return void
     */
    public function testInvalidVatRateException()
    {
        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "compound" => true,
            "main_account_amount" => 10
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 30,
            "quantity" => 1,
        ]);
        $lineItem->addVat(factory(Vat::class)->create(["rate" => 1]));
        $lineItem->save();

        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('Compound Journal Entries cannot have Vat ');
        
        $journalEntry->addLineItem($lineItem);
    }

    /**
     * Test Missing Main Account Amount Exception
     *
     * @return void
     */
    public function testMissingMainAccountAmountException()
    {
        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "compound" => true
        ]);

        $this->expectException(MissingMainAccountAmount::class);
        $this->expectExceptionMessage('Compund Journal Entries must have a Main Account Amount ');
        
        $journalEntry->save();
    }
}
