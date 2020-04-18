<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Assignment;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\ReportingPeriod;
use Ekmungai\IFRS\Models\User;

use Ekmungai\IFRS\Transactions\ClientInvoice;
use Ekmungai\IFRS\Transactions\CreditNote;
use Ekmungai\IFRS\Transactions\ClientReceipt;
use Ekmungai\IFRS\Transactions\JournalEntry;
use Ekmungai\IFRS\Transactions\SupplierBill;
use Ekmungai\IFRS\Transactions\DebitNote;
use Ekmungai\IFRS\Transactions\SupplierPayment;

use Ekmungai\IFRS\Reports\AccountSchedule;

use Ekmungai\IFRS\Exceptions\MissingAccount;

class AccountScheduleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(factory(User::class)->create());
        factory(ReportingPeriod::class)->create([
            "year" => date("Y"),
        ]);
    }

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
     * Test Client Account AccountStatement
     *
     * @return void
     */
    public function testClientAccountAccountSchedule()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $currency = factory(Currency::class)->create();

        //opening balances
        $balance = factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::D,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "currency_id" => $currency->id,
            "year" => date("Y"),
            "amount" => 50
        ]);

        //Client Receipt Transaction
        $clientReceipt = ClientReceipt::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]);
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();

        factory(Assignment::class)->create([
            'transaction_id'=> $clientReceipt->getId(),
            'cleared_id'=> $balance->id,
            'cleared_type'=> "Ekmungai\IFRS\Models\Balance",
            "amount" => 15,
        ]);

        //Client Invoice Transaction
        $clientInvoice = ClientInvoice::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create(["rate" => 16])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::OPERATING_REVENUE
            ])->id,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        //Credit Note Transaction
        $creditNote = CreditNote::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::OPERATING_REVENUE
            ])->id,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
        ]);
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        factory(Assignment::class)->create([
            'transaction_id'=> $creditNote->getId(),
            'cleared_id'=> $clientInvoice->getId(),
            "amount" => 50,
        ]);

        //Debit Journal Entry Transaction
        $debitJournalEntry = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $debitJournalEntry->setCredited(false);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 75,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]);
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 30,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create()->id,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]);

        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        factory(Assignment::class)->create([
            'transaction_id'=> $creditJournalEntry->getId(),
            'cleared_id'=> $debitJournalEntry->getId(),
            "amount" => 30,
        ]);


        $schedule = new AccountSchedule($account, $currency);
        $schedule->getTransactions();

        $this->assertEquals($schedule->transactions[0]->id, $balance->id);
        $this->assertEquals($schedule->transactions[0]->transactionType, "Opening Balance");
        $this->assertEquals($schedule->transactions[0]->originalAmount, 50);
        $this->assertEquals($schedule->transactions[0]->clearedAmount, 15);
        $this->assertEquals($schedule->transactions[0]->unclearedAmount, 35);

        $this->assertEquals($schedule->transactions[1]->id, $clientInvoice->getId());
        $this->assertEquals($schedule->transactions[1]->transactionType, "Client Invoice");
        $this->assertEquals($schedule->transactions[1]->originalAmount, 116);
        $this->assertEquals($schedule->transactions[1]->clearedAmount, 50);
        $this->assertEquals($schedule->transactions[1]->unclearedAmount, 66);

        $this->assertEquals($schedule->transactions[2]->id, $debitJournalEntry->getId());
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
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);

        $currency = factory(Currency::class)->create();

        //opening balances
        $balance = factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::C,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "currency_id" => $currency->id,
            "year" => date("Y"),
            "amount" => 60
        ]);

        //Supplier Payment Transaction
        $supplierPayment = SupplierPayment::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();

        factory(Assignment::class)->create([
            'transaction_id'=> $supplierPayment->getId(),
            'cleared_id'=> $balance->id,
            'cleared_type'=> "Ekmungai\IFRS\Models\Balance",
            "amount" => 24,
        ]);

        //Supplier Bill Transaction
        $supplierBill = SupplierBill::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 300,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create(["rate" => 16])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::DIRECT_EXPENSE
            ])->id,
        ]);
        $supplierBill->addLineItem($lineItem);

        $supplierBill->post();

        //Debit Note Transaction
        $debitNote = DebitNote::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 175,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::OVERHEAD_EXPENSE
            ])->id,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
        ]);
        $debitNote->addLineItem($lineItem);

        $debitNote->post();

        factory(Assignment::class)->create([
            'transaction_id'=> $debitNote->getId(),
            'cleared_id'=> $supplierBill->getId(),
            "amount" => 85,
        ]);

        //Debit Journal Entry Transaction
        $debitJournalEntry = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);
        $debitJournalEntry->setCredited(false);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 180,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]);
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = JournalEntry::new($account, Carbon::now(), $this->faker->word, $currency);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 240,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create()->id,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
        ]);

        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        factory(Assignment::class)->create([
            'transaction_id'=> $debitJournalEntry->getId(),
            'cleared_id'=> $creditJournalEntry->getId(),
            "amount" => 112.8,
        ]);


        $schedule = new AccountSchedule($account, $currency);
        $schedule->getTransactions();

        $this->assertEquals($schedule->transactions[0]->id, $balance->id);
        $this->assertEquals($schedule->transactions[0]->transactionType, "Opening Balance");
        $this->assertEquals($schedule->transactions[0]->originalAmount, 60);
        $this->assertEquals($schedule->transactions[0]->clearedAmount, 24);
        $this->assertEquals($schedule->transactions[0]->unclearedAmount, 36);

        $this->assertEquals($schedule->transactions[1]->id, $supplierBill->getId());
        $this->assertEquals($schedule->transactions[1]->transactionType, "Supplier Bill");
        $this->assertEquals($schedule->transactions[1]->originalAmount, 348);
        $this->assertEquals($schedule->transactions[1]->clearedAmount, 85);
        $this->assertEquals($schedule->transactions[1]->unclearedAmount, 263);

        $this->assertEquals($schedule->transactions[2]->id, $creditJournalEntry->getId());
        $this->assertEquals($schedule->transactions[2]->transactionType, "Journal Entry");
        $this->assertEquals($schedule->transactions[2]->originalAmount, 278.4);
        $this->assertEquals($schedule->transactions[2]->clearedAmount, 112.8);
        $this->assertEquals($schedule->transactions[2]->unclearedAmount, 165.6);
    }
}
