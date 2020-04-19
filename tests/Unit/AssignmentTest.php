<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Assignment;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\ReportingPeriod;
use Ekmungai\IFRS\Models\User;
use Ekmungai\IFRS\Models\Vat;
use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\Balance;

use Ekmungai\IFRS\Transactions\JournalEntry;
use Ekmungai\IFRS\Transactions\ClientInvoice;
use Ekmungai\IFRS\Transactions\ClientReceipt;

use Ekmungai\IFRS\Exceptions\InsufficientBalance;
use Ekmungai\IFRS\Exceptions\OverClearance;
use Ekmungai\IFRS\Exceptions\SelfClearance;
use Ekmungai\IFRS\Exceptions\UnpostedAssignment;
use Ekmungai\IFRS\Exceptions\UnassignableTransaction;
use Ekmungai\IFRS\Exceptions\UnclearableTransaction;
use Ekmungai\IFRS\Exceptions\InvalidClearanceAccount;
use Ekmungai\IFRS\Exceptions\InvalidClearanceCurrency;
use Ekmungai\IFRS\Exceptions\InvalidClearanceEntry;
use Ekmungai\IFRS\Exceptions\NegativeAmount;

class AssignmentTest extends TestCase
{
    /**
     * Assignment Model relationships test.
     *
     * @return void
     */
    public function testAssignmentRelationships()
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

        $this->assertEquals($assignment->transaction->transaction_no, $transaction->getTransactionNo());
        $this->assertEquals($assignment->cleared->transaction_no, $cleared->getTransactionNo());
    }

    /**
     * Test Assignment model Entity Scope.
     *
     * @return void
     */
    public function testAssignmentEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);
        $this->period = factory(ReportingPeriod::class)->create([
            "year" => date("Y"),
        ]);

        $account = factory(Account::class)->create();
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $cleared->setCredited(false);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            50
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $assignment = Assignment::new($transaction, $cleared, 50);
        $assignment->save();

        $this->assertEquals(count(Assignment::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Assignment::all()), 0);
    }

    /**
     * Test Transaction Assignment and Clearance.
     *
     * @return void
     */
    public function testAssignmentAndClearance()
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

        $cleared2 = JournalEntry::new($account, Carbon::now(), $this->faker->word);
        $cleared2->setCredited(false);

        $line = Lineitem::new($account, factory(Vat::class)->create(["rate" => 0]), 30);
        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared2->addLineItem($line);
        $cleared2->post();

        $assignment = Assignment::new($transaction, $cleared2, 15);
        $assignment->save();

        $transaction->refresh();

        $this->assertEquals($transaction->balance(), 60);
        $this->assertEquals($cleared->clearedAmount(), 50);
        $this->assertEquals($cleared2->clearedAmount(), 15);

        $transaction2 = JournalEntry::new($account, Carbon::now(), $this->faker->word);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            40
        );
        $transaction2->addLineItem($line);
        $transaction2->post();

        $assignment = Assignment::new($transaction2, $cleared, 35);
        $assignment->save();

        $cleared->refresh();
        $this->assertEquals($transaction2->balance(), 5);
        $this->assertEquals($cleared->clearedAmount(), 85);

        $balance = Balance::new($account, date("Y"), "JN01/001", 80);
        $balance->save();

        $assignment = Assignment::new($transaction, $balance, 35);
        $assignment->save();

        $transaction->refresh();
        $this->assertEquals($transaction->balance(), 25);
        $this->assertEquals($balance->clearedAmount(), 35);
    }

    /**
     * Test Insufficient Balance.
     *
     * @return void
     */
    public function testInsufficientBalance()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $cleared->setCredited(false);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InsufficientBalance::class);
        $this->expectExceptionMessage('Journal Entry Transaction does not have sufficient balance to clear 300');

        $assignment = Assignment::new($transaction, $cleared, 300);
        $assignment->save();
    }

    /**
     * Test Over Clearance.
     *
     * @return void
     */
//     public function testOverClearance()
//     {
//         $account = factory(Account::class)->create([
//             'account_type' => Account::RECEIVABLE,
//         ]);
//         $currency = factory(Currency::class)->create();

//         $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

//         $line = Lineitem::new(
//             factory(Account::class)->create(),
//             factory(Vat::class)->create(["rate" => 0]),
//             125
//         );
//         $transaction->addLineItem($line);
//         $transaction->post();

//         $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
//         $cleared->setCredited(false);

//         $line = Lineitem::new(
//             factory(Account::class)->create(),
//             factory(Vat::class)->create(["rate" => 0]),
//             100
//         );
//         $cleared->addLineItem($line);
//         $cleared->post();

//         $this->expectException(OverClearance::class);
//         $this->expectExceptionMessage('Journal Entry Transaction amount remaining to be cleared is less than 125');

//         $assignment = Assignment::new($transaction, $cleared, 125);
//         $assignment->save();
//     }

    /**
     * Test Self Clearance.
     *
     * @return void
     */
    public function testSelfClearance()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $this->expectException(SelfClearance::class);
        $this->expectExceptionMessage('Transaction cannot be used to clear itself');

        $assignment = Assignment::new($transaction, $transaction, 125);
        $assignment->save();
    }

    /**
     * Test Unassignable Transaction.
     *
     * @return void
     */
    public function testUnassignableTransaction()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = ClientInvoice::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(['account_type' => Account::OPERATING_REVENUE]),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(UnassignableTransaction::class);
        $this->expectExceptionMessage(
            "Client Invoice Transaction cannot have assignments. "
            ."Assignment Transaction must be one of: "
            ."Client Receipt, Credit Note, Supplier Payment, Debit Note, Journal Entry"
        );

        factory(Assignment::class)->create([
            'transaction_id'=> $transaction->getId(),
            'cleared_id'=> $cleared->getId(),
            "amount" => 50,
        ]);
    }

    /**
     * Test Unclearable Transaction.
     *
     * @return void
     */
    public function testUnclearableTransaction()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $transaction->setCredited(false);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = ClientReceipt::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(['account_type' => Account::BANK]),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(UnclearableTransaction::class);
        $this->expectExceptionMessage(
            "Client Receipt Transaction cannot be cleared. "
            ."Transaction to be cleared must be one of: "
            ."Client Invoice, Supplier Bill, Journal Entry"
        );

        factory(Assignment::class)->create([
            'transaction_id'=> $transaction->getId(),
            'cleared_id'=> $cleared->getId(),
            "amount" => 50,
        ]);
    }

    /**
     * Test Unposted Assignment.
     *
     * @return void
     */
    public function testUnpostedAssignment()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->save();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(UnpostedAssignment::class);
        $this->expectExceptionMessage('An Unposted Transaction cannot be Assigned or Cleared');

        $assignment = Assignment::new($transaction, $cleared, 50);
        $assignment->save();
    }

    /**
     * Test Wrong Clearance Account.
     *
     * @return void
     */
    public function testInvalidClearanceAccount()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $cleared = JournalEntry::new($account2, Carbon::now(), $this->faker->word, $currency);
        $cleared->setCredited(false);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InvalidClearanceAccount::class);
        $this->expectExceptionMessage('Assignment and Clearance Main Account must be the same');

        $assignment = Assignment::new($transaction, $cleared, 100);
        $assignment->save();
    }

    /**
     * Test Wrong Clearance Currency.
     *
     * @return void
     */
    public function testInvalidClearanceCurrency()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $currency2 = factory(Currency::class)->create();
        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency2);
        $cleared->setCredited(false);

        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InvalidClearanceCurrency::class);
        $this->expectExceptionMessage('Assignment and Clearance Currency must be the same');

        $assignment = Assignment::new($transaction, $cleared, 100);
        $assignment->save();
    }

    /**
     * Test Wrong Clearance Entry.
     *
     * @return void
     */
    public function testInvalidClearanceEntry()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $line = Lineitem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            125
        );
        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InvalidClearanceEntry::class);
        $this->expectExceptionMessage(
            "Transaction Entry increases the Main Account outstanding balance instead of reducing it"
        );

        $assignment = Assignment::new($transaction, $cleared, 100);
        $assignment->save();
    }

    /**
     * Test Negative Amount.
     *
     * @return void
     */
    public function testNegativeAmount()
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

        $assignment = Assignment::new($transaction, $cleared, -50);

        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('Assignment Amount cannot be negative');

        $assignment->save();
    }
}
