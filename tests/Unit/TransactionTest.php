<?php

namespace Tests\Unit;

use Carbon\Carbon;
use IFRS\User;
use IFRS\Exceptions\AdjustingReportingPeriod;
use IFRS\Exceptions\ClosedReportingPeriod;
use IFRS\Exceptions\HangingClearances;
use IFRS\Exceptions\MissingLineItem;
use IFRS\Exceptions\PostedTransaction;
use IFRS\Exceptions\RedundantTransaction;
use IFRS\Exceptions\UnpostedAssignment;
use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Transaction;
use IFRS\Models\Vat;
use IFRS\Tests\TestCase;
use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\JournalEntry;
use Illuminate\Support\Facades\DB;

class TransactionTest extends TestCase
{
    /**
     * Transaction Model relationships test.
     *
     * @return void
     */
    public function testTransactionRelationships()
    {
        $currency = factory(Currency::class)->create();
        $account = factory(Account::class)->create([
            'category_id' => null
        ]);
        $exchangeRate = factory(ExchangeRate::class)->create([
            "rate" => 1
        ]);

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id
        ]);

        $transaction->addLineItem(
            factory(LineItem::class)->create([
                "amount" => 100,
            ])
        );
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $cleared->addLineItem(
            factory(LineItem::class)->create([
                "amount" => 50,
            ])
        );
        $cleared->post();

        factory(Assignment::class, 5)->create([
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            "amount" => 10,
        ]);

        $this->assertEquals($transaction->currency->name, $currency->name);
        $this->assertEquals($transaction->account->name, $account->name);
        $this->assertEquals($transaction->exchangeRate->rate, $exchangeRate->rate);
        $this->assertEquals($transaction->ledgers()->get()[0]->post_account, $account->id);
        $this->assertEquals(count($transaction->assignments), 5);

        $transaction_no = $transaction->transaction_no . ' for ' . number_format($transaction->amount, 2);
        $this->assertEquals(
            $transaction->toString(true),
            Transaction::getType($transaction->transaction_type) . ': ' . $transaction_no
        );
        $this->assertEquals(
            $transaction->toString(),
            $transaction_no
        );
        $this->assertEquals($transaction->type, Transaction::getType($transaction->transaction_type));
    }

    /**
     * Test Transaction model Entity Scope.
     *
     * @return void
     */
    public function testTransactionEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();
        $this->be($user);

        $entity = new Entity();
        $entity->name = "Test Entity";
        $entity->currency_id = 2;
        $entity->save();

        factory(ReportingPeriod::class)->create([
            "calendar_year" => date("Y"),
        ]);

        factory(Transaction::class, 3)->create();

        $this->assertEquals(count(Transaction::all()), 3);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Transaction::all()), 0);
    }

    /**
     * Test Transaction Model recylcling
     *
     * @return void
     */
    public function testTransactionRecycling()
    {
        $transaction = factory(Transaction::class)->create();
        $transaction->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($transaction->recycled->first(), $recycled);
    }

    /**
     * Test Transaction Classmap
     *
     * @return void
     */
    public function testTransactionClassmap()
    {
        $this->assertEquals(Transaction::getClass('CS'), 'CashSale');
        $this->assertEquals(Transaction::getClass('IN'), 'ClientInvoice');
    }

    /**
     * Test Transaction Numbers
     *
     * @return void
     */
    public function testTransactionNumbers()
    {
        $transaction = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $this->assertEquals($transaction->transaction_no, "IN0" . $this->period->period_count . "/0001");

        $transaction = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $this->assertEquals($transaction->transaction_no, "IN0" . $this->period->period_count . "/0002");

        $period = factory(ReportingPeriod::class)->create([
            'period_count' => 2,
            'calendar_year' => Carbon::now()->addYear()->year,
        ]);

        $transaction = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now()->addYear(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $this->assertEquals($transaction->transaction_no, "IN0" . $period->period_count . "/0001");
    }

    /**
     * Test Transaction Line Items
     *
     * @return void
     */
    public function testTransactionLineItems()
    {
        $transaction = new Transaction();
        $transaction->account_id = factory(Account::class)->create([
            'category_id' => null
        ])->id;
        $transaction->exchange_rate_id = factory(ExchangeRate::class)->create()->id;
        $transaction->currency_id = factory(Currency::class)->create()->id;
        $transaction->transaction_date = Carbon::now();
        $transaction->narration = $this->faker->word;
        $transaction->transaction_no = $this->faker->word;
        $transaction->transaction_type = Transaction::JN;

        $this->assertEquals($transaction->getLineItems(), []);

        // no item duplication
        $lineItem = factory(LineItem::class)->create();
        $transaction->addLineItem($lineItem);
        $transaction->addLineItem($lineItem);

        $this->assertEquals($transaction->getLineItems(), [$lineItem]);

        // no item duplication even when they are saved
        $transaction->save();
        $transaction = Transaction::find($transaction->id);
        $this->assertEquals($transaction->getLineItems()[0]->id, $lineItem->id);

        $transaction->addLineItem($lineItem);
        $transaction->save();
        $transaction = Transaction::find($transaction->id);
        $this->assertEquals($transaction->getLineItems()[0]->id, $lineItem->id);

        $transaction->removeLineItem($lineItem);
        $this->assertEquals($transaction->getLineItems(), []);

        $transaction->addLineItem($lineItem);
        $transaction->post();

        $this->expectException(PostedTransaction::class);

        $lineItem = LineItem::find($lineItem->id);
        $transaction->removeLineItem($lineItem);
    }

    /**
     * Test Unposted Transation to be Assigned.
     *
     * @return void
     */
    public function testUnpostedTransactionAssigned()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->save();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->save();

        $this->expectException(UnpostedAssignment::class);
        $this->expectExceptionMessage('An Unposted Transaction cannot be Assigned or Cleared');

        $transaction->addAssigned(['id' => $cleared->id, 'amount' => $cleared->amount]);
    }

    /**
     * Test Transactions to be Assigned 
     *
     * @return void
     */
    public function testAssignedTransactions()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $cleared2 = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line2 = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared2->addLineItem($line2);
        $cleared2->post();

        $this->assertEquals($transaction->getAssigned(), []);

        // no assigned duplication
        $transaction->addAssigned(['id' => $cleared->id, 'amount' => 50]);
        $transaction->addAssigned(['id' => $cleared->id, 'amount' => $cleared->amount]);
        $transaction->addAssigned(['id' => $cleared2->id, 'amount' => 15]);

        $this->assertEquals($transaction->getAssigned(), [
            ['id' => $cleared->id, 'amount' => 50],
            ['id' => $cleared2->id, 'amount' => 15],
        ]);

        // processed assigned transactions
        $transaction->processAssigned();

        $cleared = Transaction::find($cleared->id);
        $cleared2 = Transaction::find($cleared2->id);

        $this->assertEquals($transaction->balance, 60);
        $this->assertEquals($cleared->cleared_amount, 50);
        $this->assertEquals($cleared2->cleared_amount, 15);
    }

    /**
     * Test Transactions to be Assigned Underclearance
     *
     * @return void
     */
    public function testAssignedTransactionsUnderclearance()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $cleared2 = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line2 = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared2->addLineItem($line2);
        $cleared2->post();

        $cleared3 = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line3 = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared3->addLineItem($line3);
        $cleared3->post();


        // overclearing
        $transaction->addAssigned(['id' => $cleared->id, 'amount' => 100]);
        $transaction->addAssigned(['id' => $cleared2->id, 'amount' => 100]);
        $transaction->addAssigned(['id' => $cleared3->id, 'amount' => 100]);

        $this->assertEquals($transaction->getAssigned(), [
            ['id' => $cleared->id, 'amount' => 100],
            ['id' => $cleared2->id, 'amount' => 25],
        ]);
    }

    /**
     * Test Missing Transaction Line Items.
     *
     * @return void
     */
    public function testMissingTransactionLineItems()
    {
        $transaction = new Transaction();
        $this->expectException(MissingLineItem::class);
        $this->expectExceptionMessage('A Transaction must have at least one LineItem to be posted');

        $transaction->post();
    }

    /**
     * Test Closed Reporting Period.
     *
     * @return void
     */
    public function testClosedReportingPeriod()
    {
        $date = Carbon::now()->subYears(5);
        factory(ReportingPeriod::class)->create([
            "calendar_year" => $date->year,
            "status" => ReportingPeriod::CLOSED,
        ]);

        $this->expectException(ClosedReportingPeriod::class);
        $this->expectExceptionMessage("Transaction cannot be saved because the Reporting Period for " . $date->year . " is closed");

        factory(Transaction::class)->create([
            "transaction_date" => $date
        ]);
    }

    /**
     * Test Adjusting Reporting Period.
     *
     * @return void
     */
    public function testAdjustingReportingPeriod()
    {
        factory(ReportingPeriod::class)->create([
            "calendar_year" => Carbon::now()->subYears(3)->year,
            "status" => ReportingPeriod::ADJUSTING,
        ]);

        $this->expectException(AdjustingReportingPeriod::class);
        $this->expectExceptionMessage('Only Journal Entry Transactions can be posted to a reporting period whose status is ADJUSTING');


        factory(Transaction::class)->create([
            "transaction_type" => Transaction::IN,
            "transaction_date" => Carbon::now()->subYears(3)
        ]);
    }

    /**
     * Test Posted Transaction Remove/Add Line Item.
     *
     * @return void
     */
    public function testPostedTransactionRemoveOrAddLineItem()
    {
        $transaction = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
                'category_id' => null
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
                "account_type" => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
        ]);

        $transaction->addLineItem($lineItem);

        $transaction->post();

        $lineItem = LineItem::find($lineItem->id);
        $this->expectException(PostedTransaction::class);
        $this->expectExceptionMessage('Cannot remove LineItem from a posted Transaction');

        $transaction->removeLineItem($lineItem);

        $this->expectException(PostedTransaction::class);
        $this->expectExceptionMessage('Cannot add LineItem to a posted Transaction');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
        ]);
        $transaction->addLineItem($lineItem);
    }

    /**
     * Test Redundant Transaction.
     *
     * @return void
     */
    public function testRedundantTransaction()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECONCILIATION,
            'category_id' => null
        ]);
        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => $account->id,
        ]);

        $this->expectException(RedundantTransaction::class);
        $this->expectExceptionMessage('A Transaction Main Account cannot be one of the Line Item Accounts');

        $transaction->addLineItem($lineItem);
    }

    /**
     * Test Hanging Clearances.
     *
     * @return void
     */
    public function testHangingClearances()
    {
        $account = factory(Account::class)->create([
            'category_id' => null
        ]);

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 50,
        ]);

        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 50,
        ]);
        $cleared->addLineItem($line);
        $cleared->post();

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 50,
        ]);
        $assignment->save();

        $this->expectException(HangingClearances::class);
        $this->expectExceptionMessage(
            'Transaction cannot be deleted because it has been used to to Clear other Transactions'
        );

        $transaction->delete();
    }

    /**
     * Test Reset Assignment.
     *
     * @return void
     */
    public function testResetAssignment()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 125,
        ]);

        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);
        $cleared->addLineItem($line);
        $cleared->post();

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 50,
        ]);
        $assignment->save();

        $cleared = Transaction::find($cleared->id);

        $this->assertEquals($transaction->balance, 75);
        $this->assertEquals($cleared->cleared_amount, 50);

        $cleared->delete();

        $transaction = Transaction::find($transaction->id);

        $this->assertEquals($transaction->balance, 125);
    }

    /**
     * Test Transaction Integrity Check.
     *
     * @return void
     */
    public function testIntegrityCheck()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);

        $transaction = new ClientInvoice([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $this->assertEquals($transaction->amount, 125);
        $this->assertTrue($transaction->has_integrity);

        //Change Transaction Ledger amounts
        DB::statement('update ' . config('ifrs.table_prefix') . 'ledgers set amount = 100 where id IN (1,2)');

        $transaction = Transaction::find($transaction->id);
        // Transaction amount has changed
        $this->assertEquals($transaction->amount, 100);

        //but Transaction Integrity is compromised
        $this->assertFalse($transaction->has_integrity);
    }

    /**
     * Test Transaction Type Names
     *
     * @return void
     */
    public function testTransactionTypeNames()
    {
        $this->assertEquals(Transaction::getTypes([Transaction::IN, Transaction::CS]), ["Client Invoice", "Cash Sale"]);
    }

    /**
     * Test Transaction predicates
     *
     * @return void
     */
    public function testTransactionPredicates()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 125,
        ]);

        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);
        $cleared->addLineItem($line);
        $cleared->post();

        $this->assertTrue($transaction->assignable);
        $this->assertTrue($transaction->clearable);

        $this->assertTrue($cleared->assignable);
        $this->assertTrue($cleared->clearable);

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 50,
        ]);
        $assignment->save();

        $cleared = Transaction::find($cleared->id);
        $transaction = Transaction::find($transaction->id);

        $this->assertTrue($transaction->assignable);
        $this->assertFalse($transaction->clearable);

        $this->assertFalse($cleared->assignable);
        $this->assertTrue($cleared->clearable);
    }
}
