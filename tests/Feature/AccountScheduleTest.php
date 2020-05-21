<?php

namespace Tests\Feature;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;

use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\CreditNote;
use IFRS\Transactions\ClientReceipt;
use IFRS\Transactions\JournalEntry;
use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\DebitNote;
use IFRS\Transactions\SupplierPayment;

use IFRS\Reports\AccountSchedule;

use IFRS\Exceptions\MissingAccount;
use IFRS\Exceptions\InvalidAccountType;

class AccountScheduleTest extends TestCase
{

    /**
     * Test Account Schedule Missing Accoount
     *
     * @return void
     */
    public function testAccountScheduleMissingAccount()
    {
        $this->expectException(MissingAccount::class);
        $this->expectExceptionMessage('Account Schedule Transactions require an Account');

        new AccountSchedule();
    }

    /**
     * Test Account Schedule Invalid Accoount Type
     *
     * @return void
     */
    public function testAccountScheduleInvalidAccountType()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::BANK,
            ]
        );

        $this->expectException(InvalidAccountType::class);
        $this->expectExceptionMessage('Schedule Account Type must be one of: Receivable, Payable');

        new AccountSchedule($account->id);
    }

    /**
     * Test Client Account AccountStatement
     *
     * @return void
     */
    public function testClientAccountAccountSchedule()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );
        $currency = factory(Currency::class)->create();

        //opening balances
        $balance = factory(Balance::class)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "currency_id" => $currency->id,
            "year" => date("Y"),
            "amount" => 50
            ]
        );

        //Client Receipt Transaction
        $clientReceipt = new ClientReceipt(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::BANK
                ]
            )->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();

        factory(Assignment::class)->create(
            [
            'transaction_id'=> $clientReceipt->id,
            'cleared_id'=> $balance->id,
            'cleared_type'=> "IFRS\Models\Balance",
            "amount" => 15,
            ]
        );

        //Client Invoice Transaction
        $clientInvoice = new ClientInvoice(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(["rate" => 16])->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OPERATING_REVENUE
                ]
            )->id,
            ]
        );
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        //Credit Note Transaction
        $creditNote = new CreditNote(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OPERATING_REVENUE
                ]
            )->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            ]
        );
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        factory(Assignment::class)->create(
            [
            'transaction_id'=> $creditNote->id,
            'cleared_id'=> $clientInvoice->id,
            "amount" => 50,
            ]
        );

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 75,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 30,
            "account_id" => factory('IFRS\Models\Account')->create()->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );

        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        factory(Assignment::class)->create(
            [
            'transaction_id'=> $creditJournalEntry->id,
            'cleared_id'=> $debitJournalEntry->id,
            "amount" => 30,
            ]
        );


        $schedule = new AccountSchedule($account->id, $currency->id);
        $schedule->attributes();
        $schedule->getTransactions();

        $this->assertEquals($schedule->transactions[0]->id, $balance->id);
        $this->assertEquals($schedule->transactions[0]->transactionType, "Opening Balance");
        $this->assertEquals($schedule->transactions[0]->originalAmount, 50);
        $this->assertEquals($schedule->transactions[0]->clearedAmount, 15);
        $this->assertEquals($schedule->transactions[0]->unclearedAmount, 35);

        $this->assertEquals($schedule->transactions[1]->id, $clientInvoice->id);
        $this->assertEquals($schedule->transactions[1]->transactionType, "Client Invoice");
        $this->assertEquals($schedule->transactions[1]->originalAmount, 116);
        $this->assertEquals($schedule->transactions[1]->clearedAmount, 50);
        $this->assertEquals($schedule->transactions[1]->unclearedAmount, 66);

        $this->assertEquals($schedule->transactions[2]->id, $debitJournalEntry->id);
        $this->assertEquals($schedule->transactions[2]->transactionType, "Journal Entry");
        $this->assertEquals($schedule->transactions[2]->originalAmount, 75);
        $this->assertEquals($schedule->transactions[2]->clearedAmount, 30);
        $this->assertEquals($schedule->transactions[2]->unclearedAmount, 45);
    }

    /**
     * Test Supplier Account AccountStatement
     *
     * @return void
     */
    public function testSupplierAccountAccountSchedule()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::PAYABLE,
            ]
        );

        $currency = factory(Currency::class)->create();

        //opening balances
        $balance = factory(Balance::class)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "currency_id" => $currency->id,
            "year" => date("Y"),
            "amount" => 60
            ]
        );

        //Supplier Payment Transaction
        $supplierPayment = new SupplierPayment(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::BANK
                ]
            )->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();

        factory(Assignment::class)->create(
            [
            'transaction_id'=> $supplierPayment->id,
            'cleared_id'=> $balance->id,
            'cleared_type'=> "IFRS\Models\Balance",
            "amount" => 24,
            ]
        );

        //Supplier Bill Transaction
        $supplierBill = new SupplierBill(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 300,
            "vat_id" => factory('IFRS\Models\Vat')->create(["rate" => 16])->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::DIRECT_EXPENSE
                ]
            )->id,
            ]
        );
        $supplierBill->addLineItem($lineItem);

        $supplierBill->post();

        //Debit Note Transaction
        $debitNote = new DebitNote(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 175,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OVERHEAD_EXPENSE
                ]
            )->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            ]
        );
        $debitNote->addLineItem($lineItem);

        $debitNote->post();

        factory(Assignment::class)->create(
            [
            'transaction_id'=> $debitNote->id,
            'cleared_id'=> $supplierBill->id,
            "amount" => 85,
            ]
        );

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            'credited' => false,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 180,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id'=> $currency->id,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 240,
            "account_id" => factory('IFRS\Models\Account')->create()->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            ]
        );

        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        factory(Assignment::class)->create(
            [
            'transaction_id'=> $debitJournalEntry->id,
            'cleared_id'=> $creditJournalEntry->id,
            "amount" => 112.8,
            ]
        );


        $schedule = new AccountSchedule($account->id, $currency->id);
        $schedule->getTransactions();

        $this->assertEquals($schedule->transactions[0]->id, $balance->id);
        $this->assertEquals($schedule->transactions[0]->transactionType, "Opening Balance");
        $this->assertEquals($schedule->transactions[0]->originalAmount, 60);
        $this->assertEquals($schedule->transactions[0]->clearedAmount, 24);
        $this->assertEquals($schedule->transactions[0]->unclearedAmount, 36);

        $this->assertEquals($schedule->transactions[1]->id, $supplierBill->id);
        $this->assertEquals($schedule->transactions[1]->transactionType, "Supplier Bill");
        $this->assertEquals($schedule->transactions[1]->originalAmount, 348);
        $this->assertEquals($schedule->transactions[1]->clearedAmount, 85);
        $this->assertEquals($schedule->transactions[1]->unclearedAmount, 263);

        $this->assertEquals($schedule->transactions[2]->id, $creditJournalEntry->id);
        $this->assertEquals($schedule->transactions[2]->transactionType, "Journal Entry");
        $this->assertEquals($schedule->transactions[2]->originalAmount, 278.4);
        $this->assertEquals($schedule->transactions[2]->clearedAmount, 112.8);
        $this->assertEquals($schedule->transactions[2]->unclearedAmount, 165.6);
    }
}
