<?php

namespace Tests\Feature;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\User;

use IFRS\Reports\AccountStatement;

use IFRS\Transactions\CashSale;
use IFRS\Transactions\ContraEntry;
use IFRS\Transactions\ClientReceipt;
use IFRS\Transactions\CashPurchase;
use IFRS\Transactions\SupplierPayment;
use IFRS\Transactions\JournalEntry;
use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\CreditNote;
use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\DebitNote;

use IFRS\Exceptions\MissingAccount;

class AccountStatementTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(factory(User::class)->create());
        factory(ReportingPeriod::class)->create(
            [
            "year" => date("Y"),
            ]
        );
    }

    /**
     * Test Account Statement Missing Accoount
     *
     * @return void
     */
    public function testAccountStatementMissingAccount()
    {
        $this->expectException(MissingAccount::class);
        $this->expectExceptionMessage('Account Statement Transactions require an Account');

        $statement = new AccountStatement();
        $statement->attributes();
        $statement->getTransactions();
    }

    /**
     * Test Bank Account AccountStatement
     *
     * @return void
     */
    public function testBankAccountAccountStatement()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::BANK,
            ]
        );

        //opening balances
        factory(Balance::class, 3)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "year" => date("Y"),
            "amount" => 50
            ]
        );

        factory(Balance::class, 2)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "year" => date("Y"),
            "amount" => 40
            ]
        );

        //Cash Sale Transaction
        $cashSale = new CashSale(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $cashSale->save();

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
                "account_type" => Account::OPERATING_REVENUE
                ]
            )->id,
            ]
        );
        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        //Credit Contra Entry Transaction
        $creditContraEntry = new ContraEntry(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                'account_type' => Account::BANK,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "account_id" => $account->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $creditContraEntry->addLineItem($lineItem);

        $creditContraEntry->post();

        //Debit Contra Entry Transaction
        $debitContraEntry = new ContraEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
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
        $debitContraEntry->addLineItem($lineItem);

        $debitContraEntry->post();

        //Client Receipt Transaction
        $clientReceipt = new ClientReceipt(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                'account_type' => Account::RECEIVABLE,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "account_id" => $account->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();

        //Cash Purchase Transaction
        $cashPurchase = new CashPurchase(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 75,
            "vat_id" => factory('IFRS\Models\Vat')->create(["rate" => 16])->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OTHER_EXPENSE
                ]
            )->id,
            ]
        );
        $cashPurchase->addLineItem($lineItem);

        $cashPurchase->post();

        //Supplier Payment Transaction
        $supplierPayment = new SupplierPayment(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                'account_type' => Account::PAYABLE,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            "account_id" => $account->id,
            ]
        );
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        //Debit Joutnal Entry Transaction
        $debitJournalEntry = new JournalEntry(
            [
            "account_id" => factory('IFRS\Models\Account')->create()->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "account_id" => $account->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        $statement = new AccountStatement($account->id);
        $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], 70);

        $this->assertEquals($statement->transactions[0]->id, $cashSale->id);
        $this->assertEquals($statement->transactions[0]->debit, $cashSale->getAmount());
        $this->assertEquals($statement->transactions[0]->balance, 186);

        $this->assertEquals($statement->transactions[1]->id, $creditContraEntry->id);
        $this->assertEquals($statement->transactions[1]->credit, $creditContraEntry->getAmount());
        $this->assertEquals($statement->transactions[1]->balance, 136);

        $this->assertEquals($statement->transactions[2]->id, $debitContraEntry->id);
        $this->assertEquals($statement->transactions[2]->debit, $debitContraEntry->getAmount());
        $this->assertEquals($statement->transactions[2]->balance, 186);

        $this->assertEquals($statement->transactions[3]->id, $clientReceipt->id);
        $this->assertEquals($statement->transactions[3]->debit, $clientReceipt->getAmount());
        $this->assertEquals($statement->transactions[3]->balance, 286);

        $this->assertEquals($statement->transactions[4]->id, $cashPurchase->id);
        $this->assertEquals($statement->transactions[4]->credit, $cashPurchase->getAmount());
        $this->assertEquals($statement->transactions[4]->balance, 199);

        $this->assertEquals($statement->transactions[5]->id, $supplierPayment->id);
        $this->assertEquals($statement->transactions[5]->credit, $supplierPayment->getAmount());
        $this->assertEquals($statement->transactions[5]->balance, 149);

        $this->assertEquals($statement->transactions[6]->id, $creditJournalEntry->id);
        $this->assertEquals($statement->transactions[6]->credit, $creditJournalEntry->getAmount());
        $this->assertEquals($statement->transactions[6]->balance, 99);

        $this->assertEquals($statement->transactions[7]->id, $debitJournalEntry->id);
        $this->assertEquals($statement->transactions[7]->debit, $debitJournalEntry->getAmount());
        $this->assertEquals($statement->transactions[7]->balance, 149);

        $this->assertEquals($statement->balances['closing'], 149);
    }

    /**
     * Test Client Account AccountStatement
     *
     * @return void
     */
    public function testClientAccountAccountStatement()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );

        //opening balances
        factory(Balance::class, 3)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "year" => date("Y"),
            "amount" => 50
            ]
        );

        factory(Balance::class, 2)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "year" => date("Y"),
            "amount" => 40
            ]
        );

        //Client Invoice Transaction
        $clientInvoice = new ClientInvoice(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
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

        //Client Receipt Transaction
        $clientReceipt = new ClientReceipt(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
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

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry(
            [
            "account_id" => factory('IFRS\Models\Account')->create()->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "account_id" => $account->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        $statement = new AccountStatement($account->id);
        $statement->getTransactions();

        //dd($statement->toString());
        $this->assertEquals($statement->balances['opening'], 70);

        $this->assertEquals($statement->transactions[0]->id, $clientInvoice->id);
        $this->assertEquals($statement->transactions[0]->debit, $clientInvoice->getAmount());
        $this->assertEquals($statement->transactions[0]->balance, 186);

        $this->assertEquals($statement->transactions[1]->id, $creditNote->id);
        $this->assertEquals($statement->transactions[1]->credit, $creditNote->getAmount());
        $this->assertEquals($statement->transactions[1]->balance, 128);

        $this->assertEquals($statement->transactions[2]->id, $clientReceipt->id);
        $this->assertEquals($statement->transactions[2]->credit, $clientReceipt->getAmount());
        $this->assertEquals($statement->transactions[2]->balance, 28);

        $this->assertEquals($statement->transactions[3]->id, $creditJournalEntry->id);
        $this->assertEquals($statement->transactions[3]->credit, $creditJournalEntry->getAmount());
        $this->assertEquals($statement->transactions[3]->balance, -22);

        $this->assertEquals($statement->transactions[4]->id, $debitJournalEntry->id);
        $this->assertEquals($statement->transactions[4]->debit, $debitJournalEntry->getAmount());
        $this->assertEquals($statement->transactions[4]->balance, 28);

        $this->assertEquals($statement->balances['closing'], 28);
    }

    /**
     * Test Supplier Account AccountStatement
     *
     * @return void
     */
    public function testSupplierAccountAccountStatement()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::PAYABLE,
            ]
        );

        //opening balances
        factory(Balance::class, 3)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "year" => date("Y"),
            "amount" => 50
            ]
        );

        factory(Balance::class, 2)->create(
            [
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create(
                [
                "rate" => 1,
                ]
            )->id,
            "year" => date("Y"),
            "amount" => 40
            ]
        );

        //Supplier Bill Transaction
        $supplierBill = new SupplierBill(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(["rate" => 16])->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OPERATING_EXPENSE
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
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
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

        //Supplier Payment Transaction
        $supplierPayment = new SupplierPayment(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
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

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry(
            [
            "account_id" => factory('IFRS\Models\Account')->create()->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 50,
            "account_id" => $account->id,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            ]
        );
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        $statement = new AccountStatement($account->id);
        $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], -70);

        $this->assertEquals($statement->transactions[0]->id, $supplierBill->id);
        $this->assertEquals($statement->transactions[0]->credit, $supplierBill->getAmount());
        $this->assertEquals($statement->transactions[0]->balance, -186);

        $this->assertEquals($statement->transactions[1]->id, $debitNote->id);
        $this->assertEquals($statement->transactions[1]->debit, $debitNote->getAmount());
        $this->assertEquals($statement->transactions[1]->balance, -128);

        $this->assertEquals($statement->transactions[2]->id, $supplierPayment->id);
        $this->assertEquals($statement->transactions[2]->debit, $supplierPayment->getAmount());
        $this->assertEquals($statement->transactions[2]->balance, -28);

        $this->assertEquals($statement->transactions[3]->id, $creditJournalEntry->id);
        $this->assertEquals($statement->transactions[3]->credit, $creditJournalEntry->getAmount());
        $this->assertEquals($statement->transactions[3]->balance, -78);

        $this->assertEquals($statement->transactions[4]->id, $debitJournalEntry->id);
        $this->assertEquals($statement->transactions[4]->debit, $debitJournalEntry->getAmount());
        $this->assertEquals($statement->transactions[4]->balance, -28);

        $this->assertEquals($statement->balances['closing'], -28);
    }
}
