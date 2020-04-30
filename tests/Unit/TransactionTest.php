<?php

namespace Tests\Unit;

use Carbon\Carbon;
use DB;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Assignment;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\Entity;
use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\RecycledObject;
use Ekmungai\IFRS\Models\ReportingPeriod;
use Ekmungai\IFRS\Models\Transaction;
use Ekmungai\IFRS\Models\User;
use Ekmungai\IFRS\Models\Vat;
use Ekmungai\IFRS\Models\Ledger;

use Ekmungai\IFRS\Transactions\JournalEntry;
use Ekmungai\IFRS\Transactions\ClientInvoice;

use Ekmungai\IFRS\Exceptions\RedundantTransaction;
use Ekmungai\IFRS\Exceptions\HangingClearances;
use Ekmungai\IFRS\Exceptions\MissingLineItem;
use Ekmungai\IFRS\Exceptions\PostedTransaction;

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
        $exchangeRate = factory(ExchangeRate::class)->create([
            "rate" => 1
        ]);

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $transaction->addLineItem(factory(LineItem::class)->create([
            "amount" => 100,
        ]));
        $transaction->post();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $cleared->setCredited(false);
        $cleared->addLineItem(factory(LineItem::class)->create([
            "amount" => 50,
        ]));
        $cleared->post();

        factory(Assignment::class, 5)->create(
            [
                'transaction_id'=> $transaction->getId(),
                'cleared_id'=> $cleared->getId(),
                "amount" => 10,
            ]
        );

        $transaction = Transaction::find($transaction->getId());

        $this->assertEquals($transaction->currency->name, $currency->name);
        $this->assertEquals($transaction->account->name, $account->name);
        $this->assertEquals($transaction->exchangeRate->rate, $exchangeRate->rate);
        $this->assertEquals($transaction->ledgers()->get()[0]->post_account, $account->id);
        $this->assertEquals(count($transaction->assignments), 5);
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
        $transaction = ClientInvoice::new(
            factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $this->assertEquals($transaction->getTransactionNo(), "IN0".$this->period->period_count."/0001");

        $transaction = ClientInvoice::new(
            factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $this->assertEquals($transaction->getTransactionNo(), "IN0".$this->period->period_count."/0002");

        $period= factory(ReportingPeriod::class)->create([
            'period_count' => 2,
            'year' => Carbon::now()->addYear()->year,
        ]);

        $transaction = ClientInvoice::new(
            factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now()->addYear(),
            $this->faker->word
        );
        $transaction->save();

        $this->assertEquals($transaction->getTransactionNo(), "IN0".$period->period_count."/0001");
    }

    /**
     * Test Transaction Line Items
     *
     * @return void
     */
    public function testTransactionLineItems()
    {
        $transaction = new Transaction();
        $transaction->account_id = factory('Ekmungai\IFRS\Models\Account')->create()->id;
        $transaction->exchange_rate_id = factory('Ekmungai\IFRS\Models\ExchangeRate')->create()->id;
        $transaction->currency_id = factory('Ekmungai\IFRS\Models\Currency')->create()->id;
        $transaction->date = Carbon::now();
        $transaction->narration = $this->faker->word;
        $transaction->transaction_no = $this->faker->word;
        $transaction->transaction_type = Transaction::JN;
        $transaction->amount = $this->faker->randomFloat(2);

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
     * Test Posted Transaction Remove Line Item.
     *
     * @return void
     */
    public function testPostedTransactionRemoveLineItem()
    {
        $transaction = JournalEntry::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
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
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);

        $transaction->addLineItem($lineItem);

        $transaction->post();

        $lineItem = LineItem::find($lineItem->id);
        $this->expectException(PostedTransaction::class);
        $this->expectExceptionMessage('Cannot remove LineItem from a posted Transaction');

        $transaction->removeLineItem($lineItem);
    }

    /**
     * Test Redundant Transaction.
     *
     * @return void
     */
    public function testRedundantTransaction()
    {
        $account = factory('Ekmungai\IFRS\Models\Account')->create([
            'account_type' => Account::RECONCILIATION,
        ]);
        $transaction = JournalEntry::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
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
        $account = factory(Account::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            50
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word);
        $cleared->setCredited(false);

        $line = LineItem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            50
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $assignment = Assignment::new($transaction, $cleared, 50);
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
        ]);

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word);
        $cleared->setCredited(false);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $assignment = Assignment::new($transaction, $cleared, 50);
        $assignment->save();

        $this->assertEquals($transaction->balance(), 75);
        $this->assertEquals($cleared->clearedAmount(), 50);

        $cleared->delete();

        $transaction->refresh();
        $cleared->refresh();

        $this->assertEquals($transaction->balance(), 125);
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
        ]);

        $transaction = ClientInvoice::new($account, Carbon::now(), $this->faker->word);

        $line = Lineitem::new(
            factory(Account::class)->create([
                'account_type' => Account::OPERATING_REVENUE
            ]),
            factory(Vat::class)->create(["rate" => 0]),
            125
            );
        $transaction->addLineItem($line);
        $transaction->post();

        $this->assertEquals($transaction->getAmount(), 125);
        $this->assertTrue($transaction->checkIntegrity());

        //Change Transaction Ledger amounts
        DB::statement('update ledgers set amount = 100 where id IN (1,2)');

        // Transaction amount has changed
        $transaction->refresh();
        $this->assertEquals($transaction->getAmount(), 100);

        //but Transaction Integrity is compromised
        $this->assertFalse($transaction->checkIntegrity());
    }
}
