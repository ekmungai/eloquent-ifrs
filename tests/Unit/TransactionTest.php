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
        $account = factory(Account::class)->create();
        $exchangeRate = factory(ExchangeRate::class)->create(
            [
            "rate" => 1
            ]
        );

        $transaction = new JournalEntry(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id
            ]
        );

        $transaction->addLineItem(
            factory(LineItem::class)->create(
                [
                "amount" => 100,
                ]
            )
        );
        $transaction->post();

        $cleared = new JournalEntry(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
            ]
        );

        $cleared->addLineItem(
            factory(LineItem::class)->create(
                [
                "amount" => 50,
                ]
            )
        );
        $cleared->post();

        factory(Assignment::class, 5)->create(
            [
                'transaction_id'=> $transaction->id,
                'cleared_id'=> $cleared->id,
                "amount" => 10,
            ]
        );

        $this->assertEquals($transaction->currency->name, $currency->name);
        $this->assertEquals($transaction->account->name, $account->name);
        $this->assertEquals($transaction->exchangeRate->rate, $exchangeRate->rate);
        $this->assertEquals($transaction->ledgers()->get()[0]->post_account, $account->id);
        $this->assertEquals(count($transaction->assignments), 5);
        $this->assertEquals(
            $transaction->identifier(),
            Transaction::getType($transaction->transaction_type).': '.$transaction->transaction_no
        );
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

        factory(ReportingPeriod::class)->create(
            [
            "calendar_year" => date("Y"),
            ]
        );

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
     * Test Transaction Numbers
     *
     * @return void
     */
    public function testTransactionNumbers()
    {
        $transaction = new ClientInvoice(
            [
            "account_id" => factory(Account::class)->create(
                [
                'account_type' => Account::RECEIVABLE,
                ]
            )->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $transaction->save();

        $this->assertEquals($transaction->transaction_no, "IN0".$this->period->period_count."/0001");

        $transaction = new ClientInvoice(
            [
            "account_id" => factory(Account::class)->create(
                [
                'account_type' => Account::RECEIVABLE,
                ]
            )->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $transaction->save();

        $this->assertEquals($transaction->transaction_no, "IN0".$this->period->period_count."/0002");

        $period= factory(ReportingPeriod::class)->create(
            [
            'period_count' => 2,
            'calendar_year' => Carbon::now()->addYear()->year,
            ]
        );

        $transaction = new ClientInvoice(
            [
            "account_id" => factory(Account::class)->create(
                [
                'account_type' => Account::RECEIVABLE,
                ]
            )->id,
            "transaction_date" => Carbon::now()->addYear(),
            "narration" => $this->faker->word,
            ]
        );
        $transaction->save();

        $this->assertEquals($transaction->transaction_no, "IN0".$period->period_count."/0001");
    }

    /**
     * Test Transaction Line Items
     *
     * @return void
     */
    public function testTransactionLineItems()
    {
        $transaction = new Transaction();
        $transaction->account_id = factory('IFRS\Models\Account')->create()->id;
        $transaction->exchange_rate_id = factory('IFRS\Models\ExchangeRate')->create()->id;
        $transaction->currency_id = factory('IFRS\Models\Currency')->create()->id;
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
        factory(ReportingPeriod::class)->create(
            [
                "calendar_year" => $date->year,
                "status" => ReportingPeriod::CLOSED,
            ]
        );

        $this->expectException(ClosedReportingPeriod::class);
        $this->expectExceptionMessage("Transaction cannot be saved because the Reporting Period for ".$date->year." is closed");

        factory(Transaction::class)->create(
            [
                "transaction_date" => $date
            ]
        );
    }

    /**
     * Test Adjusting Reporting Period.
     *
     * @return void
     */
    public function testAdjustingReportingPeriod()
    {
        factory(ReportingPeriod::class)->create(
            [
                "calendar_year" => Carbon::now()->subYears(3)->year,
                "status" => ReportingPeriod::ADJUSTING,
            ]
            );

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
        $transaction = new JournalEntry(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                'account_type' => Account::RECONCILIATION,
                ]
            )->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::RECONCILIATION
                ]
            )->id,
            ]
        );

        $transaction->addLineItem($lineItem);

        $transaction->post();

        $lineItem = LineItem::find($lineItem->id);
        $this->expectException(PostedTransaction::class);
        $this->expectExceptionMessage('Cannot remove LineItem from a posted Transaction');

        $transaction->removeLineItem($lineItem);

        $this->expectException(PostedTransaction::class);
        $this->expectExceptionMessage('Cannot add LineItem to a posted Transaction');

        $lineItem = factory(LineItem::class)->create(
            [
                "amount" => 100,
                "vat_id" => factory('IFRS\Models\Vat')->create(
                    [
                        "rate" => 16
                    ]
                    )->id,
                "account_id" => factory('IFRS\Models\Account')->create(
                    [
                        "account_type" => Account::RECONCILIATION
                    ]
                    )->id,
            ]
        );
        $transaction->addLineItem($lineItem);
    }

    /**
     * Test Redundant Transaction.
     *
     * @return void
     */
    public function testRedundantTransaction()
    {
        $account = factory('IFRS\Models\Account')->create(
            [
            'account_type' => Account::RECONCILIATION,
            ]
        );
        $transaction = new JournalEntry(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            "account_id" => $account->id,
            ]
        );

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
        $account = factory(Account::class)->create();

        $transaction = new JournalEntry(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $line = new LineItem(
            [
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 50,
            ]
        );

        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
            ]
        );

        $line = new LineItem(
            [
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 50,
            ]
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $assignment = new Assignment(
            [
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->getClearedType(),
            'amount' => 50,
            ]
        );
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
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );

        $transaction = new JournalEntry(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $line = new LineItem(
            [
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
            ]
        );

        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
            ]
        );

        $line = new LineItem(
            [
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
            ]
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $assignment = new Assignment(
            [
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->getClearedType(),
            'amount' => 50,
            ]
        );
        $assignment->save();

        $cleared = Transaction::find($cleared->id);

        $this->assertEquals($transaction->balance(), 75);
        $this->assertEquals($cleared->clearedAmount(), 50);

        $cleared->delete();

        $transaction = Transaction::find($transaction->id);

        $this->assertEquals($transaction->balance(), 125);
    }

    /**
     * Test Transaction Integrity Check.
     *
     * @return void
     */
    public function testIntegrityCheck()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );

        $transaction = new ClientInvoice(
            [
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $line = new LineItem(
            [
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create(
                [
                'account_type' => Account::OPERATING_REVENUE
                ]
            )->id,
            'amount' => 125,
            ]
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $this->assertEquals($transaction->getAmount(), 125);
        $this->assertTrue($transaction->checkIntegrity());

        //Change Transaction Ledger amounts
        DB::statement('update '.config('ifrs.table_prefix').'ledgers set amount = 100 where id IN (1,2)');

        $transaction = Transaction::find($transaction->id);
        // Transaction amount has changed
        $this->assertEquals($transaction->getAmount(), 100);

        //but Transaction Integrity is compromised
        $this->assertFalse($transaction->checkIntegrity());
    }

    /**
     * Test Transaction Type Names
     *
     * @return void
     */
    public function testTransactionTypeNames()
    {
        $this->assertEquals(Transaction::getTypes([Transaction::IN,Transaction::CS]), ["Client Invoice","Cash Sale"]);
    }
}
