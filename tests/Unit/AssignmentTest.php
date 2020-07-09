<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Currency;
use IFRS\Models\ReportingPeriod;
use IFRS\User;
use IFRS\Models\Vat;
use IFRS\Models\LineItem;
use IFRS\Models\Balance;

use IFRS\Transactions\JournalEntry;
use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\ClientReceipt;

use IFRS\Exceptions\InsufficientBalance;
use IFRS\Exceptions\OverClearance;
use IFRS\Exceptions\SelfClearance;
use IFRS\Exceptions\UnpostedAssignment;
use IFRS\Exceptions\UnassignableTransaction;
use IFRS\Exceptions\UnclearableTransaction;
use IFRS\Exceptions\InvalidClearanceAccount;
use IFRS\Exceptions\InvalidClearanceCurrency;
use IFRS\Exceptions\InvalidClearanceEntry;
use IFRS\Exceptions\NegativeAmount;
use IFRS\Models\Transaction;
use IFRS\Exceptions\MissingForexAccount;
use IFRS\Models\ExchangeRate;

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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
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
            'account_id' => factory(Account::class)->create()->id,
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

        $this->assertEquals($assignment->transaction->transaction_no, $transaction->transaction_no);
        $this->assertEquals($assignment->cleared->transaction_no, $cleared->transaction_no);
        $this->assertEquals($assignment->cleared->transaction_no, $cleared->transaction_no);
        $this->assertEquals(
            $assignment->toString(true),
            'Assignment: ' . $assignment->transaction->toString() . ' on ' . $assignment->assignment_date
        );
        $this->assertEquals(
            $assignment->toString(),
            $assignment->transaction->toString() . ' on ' . $assignment->assignment_date
        );
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
            "calendar_year" => date("Y"),
        ]);

        $account = factory(Account::class)->create();
        $currency = factory(Currency::class)->create();

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
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
            'account_id' => factory(Account::class)->create()->id,
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

        $cleared2 = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);
        $cleared2->addLineItem($line);
        $cleared2->post();

        $assignment =  new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared2->id,
            'cleared_type' => $cleared2->cleared_type,
            'amount' => 15,
        ]);
        $assignment->save();


        $cleared2 = Transaction::find($cleared2->id);

        $this->assertEquals($transaction->balance, 60);
        $this->assertEquals($cleared->cleared_amount, 50);
        $this->assertEquals($cleared2->cleared_amount, 15);

        $transaction2 = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 40,
        ]);
        $transaction2->addLineItem($line);
        $transaction2->post();

        $assignment =  new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction2->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 35,
        ]);
        $assignment->save();

        $cleared = Transaction::find($cleared->id);

        $this->assertEquals($transaction2->balance, 5);
        $this->assertEquals($cleared->cleared_amount, 85);

        $balance = new Balance([
            'account_id' => $account->id,
            'year' => date("Y"),
            'transaction_no' => "JN01/0001",
            'transaction_date' => Carbon::now()->subYears(1.5),
            'amount' => 80,
        ]);
        $balance->save();

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $balance->id,
            'cleared_type' => $balance->cleared_type,
            'amount' => 35,
        ]);
        $assignment->save();

        $balance = Balance::find($balance->id);

        $this->assertEquals($transaction->balance, 25);
        $this->assertEquals($balance->cleared_amount, 35);
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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InsufficientBalance::class);
        $this->expectExceptionMessage('Journal Entry Transaction does not have sufficient balance to clear 300');

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 300,
        ]);
        $assignment->save();
    }

    /**
     * Test Over Clearance.
     *
     * @return void
     */
    public function testOverClearance()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(OverClearance::class);
        $this->expectExceptionMessage('Journal Entry Transaction amount remaining to be cleared is less than 125');

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 125,
        ]);
        $assignment->save();
    }

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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $this->expectException(SelfClearance::class);
        $this->expectExceptionMessage('Transaction cannot be used to clear itself');

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $transaction->id,
            'cleared_type' => $transaction->cleared_type,
            'amount' => 125,
        ]);
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

        $transaction = new ClientInvoice([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create(['account_type' => Account::OPERATING_REVENUE])->id,
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
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(UnassignableTransaction::class);
        $this->expectExceptionMessage(
            "Client Invoice Transaction cannot have assignments. "
                . "Assignment Transaction must be one of: "
                . "Client Receipt, Credit Note, Supplier Payment, Debit Note, Journal Entry"
        );

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 50,
        ]);
        $assignment->save();
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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new ClientReceipt([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create(['account_type' => Account::BANK])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(UnclearableTransaction::class);
        $this->expectExceptionMessage(
            "Client Receipt Transaction cannot be cleared. "
                . "Transaction to be cleared must be one of: "
                . "Client Invoice, Supplier Bill, Journal Entry"
        );

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 50,
        ]);
        $assignment->save();
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

        $transaction = $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
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
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(UnpostedAssignment::class);
        $this->expectExceptionMessage('An Unposted Transaction cannot be Assigned or Cleared');

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 50,
        ]);
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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);

        $cleared = new JournalEntry([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create(['account_type' => Account::BANK])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InvalidClearanceAccount::class);
        $this->expectExceptionMessage('Assignment and Clearance Main Account must be the same');

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 100,
        ]);
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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $currency2 = factory(Currency::class)->create();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency2->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create(['account_type' => Account::BANK])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InvalidClearanceCurrency::class);
        $this->expectExceptionMessage('Assignment and Clearance Currency must be the same');

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 100,
        ]);
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

        $transaction = $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $currency->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
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
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(InvalidClearanceEntry::class);
        $this->expectExceptionMessage(
            "Transaction Entry increases the Main Account outstanding balance instead of reducing it"
        );

        $assignment =  new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 100,
        ]);
        $assignment->save();
    }

    /**
     * Test Assignment Negative Amount.
     *
     * @return void
     */
    public function testAssignmentNegativeAmount()
    {
        $account = factory(Account::class)->create();

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('Assignment Amount cannot be negative');

        $assignment =  new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => -50,
        ]);
        $assignment->save();
    }

    /**
     * Test Transaction bulk Clearance.
     *
     * @return void
     */
    public function testTransactionBulkClearance()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
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
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 75,
        ]);
        $cleared->addLineItem($line);
        $cleared->post();

        $cleared2 = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 25,
        ]);
        $cleared2->addLineItem($line);
        $cleared2->post();

        Assignment::bulkAssign($transaction);

        $cleared = Transaction::find($cleared->id);
        $cleared2 = Transaction::find($cleared2->id);

        $this->assertEquals($transaction->balance, 25);
        $this->assertEquals($cleared->cleared_amount, 75);
        $this->assertEquals($cleared2->cleared_amount, 25);
    }

    /**
     * Test Missing Forex Account.
     *
     * @return void
     */
    public function testMissingForexAccount()
    {
        $account = factory(Account::class)->create();
        $currency  = factory(Currency::class)->create();

        $transaction  = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 125,
        ]);
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new JournalEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                'currency_id' => $currency->id,
                'rate' => 10
            ])->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 50,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $this->expectException(MissingForexAccount::class);
        $this->expectExceptionMessage('A Forex Differences Account is required for Assignment Transactions with different exchange rates');

        $assignment =  new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 10,
        ]);
        $assignment->save();
    }
}
