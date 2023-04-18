<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Tests\Feature;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\Transaction;
use IFRS\Models\Vat;
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

        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
            'category_id' => null
        ]);

        $this->expectException(InvalidAccountType::class);
        $this->expectExceptionMessage('Schedule Account Type must be one of: Receivable, Payable');

        new AccountSchedule($account->id);
    }

    /**
     * Test Client Account AccountStatement
     *
     * @return void
     */
    public function testClientAccountScheduleTest()
    {

        $currency = factory(Currency::class)->create();
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency->id,
        ]);

        //opening balances
        $balance = factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
                'currency_id' => $currency->id,
            ])->id,
            "currency_id" => $currency->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 50
        ]);

        //Client Receipt Transaction
        $clientReceipt = new ClientReceipt([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            "quantity" => 1,
        ]);
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();

        factory(Assignment::class)->create([
            'transaction_id' => $clientReceipt->id,
            'cleared_id' => $balance->id,
            'cleared_type' => "IFRS\Models\Balance",
            "amount" => 15,
        ]);

        //Client Invoice Transaction
        $clientInvoice = new ClientInvoice([
            "account_id" => $account->id,
            "date" => Carbon::now()->subMonth(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null,
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        //Credit Note Transaction
        $creditNote = new CreditNote([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        factory(Assignment::class)->create([
            'transaction_id' => $creditNote->id,
            'cleared_id' => $clientInvoice->id,
            "amount" => 50,
        ]);

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now()->subMonths(2),
            "narration" => $this->faker->word,
            "credited" => false,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 75,
            "quantity" => 1,
        ]);
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 30,
            "account_id" => factory(Account::class)->create([
                'category_id' => null,
            ])->id,
            "quantity" => 1,
        ]);

        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        factory(Assignment::class)->create([
            'transaction_id' => $creditJournalEntry->id,
            'cleared_id' => $debitJournalEntry->id,
            "amount" => 30,
        ]);


        $schedule = new AccountSchedule($account->id, $currency->id);
        $schedule->attributes();
        $schedule->getTransactions();

        $this->assertEquals($schedule->transactions[0]->id, $balance->id);
        $this->assertEquals($schedule->transactions[0]->transactionType, Transaction::getType($balance->transaction_type));
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

        $this->assertEquals($schedule->balances["originalAmount"], 241);
        $this->assertEquals($schedule->balances["amountCleared"], 95);
        $this->assertEquals($schedule->balances["unclearedAmount"], 146);
        $this->assertEquals($schedule->balances["totalAge"], 365);
        $this->assertEquals($schedule->balances["averageAge"], 122);
    }

    /**
     * Test Supplier Account AccountStatement
     *
     * @return void
     */
    public function testSupplierAccountAccountSchedule()
    {
        $currency = factory(Currency::class)->create();
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency->id,
        ]);

        //opening balances
        $balance = factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
                'currency_id' => $currency->id,
            ])->id,
            "currency_id" => $currency->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 60
        ]);

        //Supplier Payment Transaction
        $supplierPayment = new SupplierPayment([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            "quantity" => 1,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();

        factory(Assignment::class)->create([
            'transaction_id' => $supplierPayment->id,
            'cleared_id' => $balance->id,
            'cleared_type' => "IFRS\Models\Balance",
            "amount" => 24,
        ]);

        //Supplier Bill Transaction
        $supplierBill = new SupplierBill([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 300,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $supplierBill->addLineItem($lineItem);

        $supplierBill->post();

        //Debit Note Transaction
        $debitNote = new DebitNote([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 175,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OVERHEAD_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $debitNote->addLineItem($lineItem);

        $debitNote->post();

        factory(Assignment::class)->create([
            'transaction_id' => $debitNote->id,
            'cleared_id' => $supplierBill->id,
            "amount" => 85,
        ]);

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
            'credited' => false,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 180,
            "quantity" => 1,
        ]);
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 240,
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();

        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        factory(Assignment::class)->create([
            'transaction_id' => $debitJournalEntry->id,
            'cleared_id' => $creditJournalEntry->id,
            "amount" => 112.8,
        ]);


        $schedule = new AccountSchedule($account->id, $currency->id);
        $schedule->getTransactions();

        $this->assertEquals($schedule->transactions[0]->id, $balance->id);
        $this->assertEquals($schedule->transactions[0]->transactionType, Transaction::getType($balance->transaction_type));
        $this->assertEquals($schedule->transactions[0]->originalAmount, 60);
        $this->assertEquals($schedule->transactions[0]->amountCleared, 24);
        $this->assertEquals($schedule->transactions[0]->unclearedAmount, 36);

        $this->assertEquals($schedule->transactions[1]->id, $supplierBill->id);
        $this->assertEquals($schedule->transactions[1]->transactionType, "Supplier Bill");
        $this->assertEquals($schedule->transactions[1]->originalAmount, 348);
        $this->assertEquals($schedule->transactions[1]->amountCleared, 85);
        $this->assertEquals($schedule->transactions[1]->unclearedAmount, 263);

        $this->assertEquals($schedule->transactions[2]->id, $creditJournalEntry->id);
        $this->assertEquals($schedule->transactions[2]->transactionType, "Journal Entry");
        $this->assertEquals($schedule->transactions[2]->originalAmount, 278.4);
        $this->assertEquals($schedule->transactions[2]->amountCleared, 112.8);
        $this->assertEqualsWithDelta($schedule->transactions[2]->unclearedAmount, 165.6, 0.1);

        $this->assertEquals($schedule->balances['originalAmount'], 686.4);
        $this->assertEquals($schedule->balances['amountCleared'], 221.8);
        $this->assertEqualsWithDelta($schedule->balances['unclearedAmount'], 464.6, 0.1);
        $this->assertEquals($schedule->balances['totalAge'], 365);
        $this->assertEquals($schedule->balances['averageAge'], 122.0);
    }

    /**
     * Test AccountSchedule Currency filters
     *
     * @return void
     */
    public function testAccountScheduleCurrencyFilters()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null
        ]);

        $rate = factory(ExchangeRate::class)->create([
            'rate' => 105
        ]);

        $baseCurrency = Auth::user()->entity->currency_id;

        // Base currency opening balances
        $balance1 = factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "currency_id" => $baseCurrency,
            'reporting_period_id' => $this->period->id,
            "balance" => 60
        ]);

        // Base currency Supplier Payment Transaction
        $supplierPayment1 = new SupplierPayment([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $baseCurrency,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK,
                'category_id' => null,
                "currency_id" => $baseCurrency,
            ])->id,
            "quantity" => 1,
        ]);
        
        $supplierPayment1->addLineItem($lineItem);

        $supplierPayment1->post();

        factory(Assignment::class)->create([
            'transaction_id' => $supplierPayment1->id,
            'cleared_id' => $balance1->id,
            'cleared_type' => "IFRS\Models\Balance",
            "amount" => 24,
        ]);
        
        // Foreign currency opening balances
        $balance2 = factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => $rate->id,
            "currency_id" => $rate->currency->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 60
        ]);

        // Foreign currency Supplier Payment Transaction
        $supplierPayment2 = new SupplierPayment([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $rate->currency->id,
            "exchange_rate_id" => $rate->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK,
                'category_id' => null,
                "currency_id" => $rate->currency->id,
            ])->id,
            "quantity" => 1,
        ]);
        $supplierPayment2->addLineItem($lineItem);

        $supplierPayment2->post();

        factory(Assignment::class)->create([
            'transaction_id' => $supplierPayment2->id,
            'cleared_id' => $balance2->id,
            'cleared_type' => "IFRS\Models\Balance",
            "amount" => 24,
        ]);

        // Base currency Supplier Bill Transaction
        $supplierBill1 = new SupplierBill([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $baseCurrency,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 300,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $supplierBill1->addLineItem($lineItem);

        $supplierBill1->post();

        // Base currency Debit Note Transaction
        $debitNote1 = new DebitNote([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $baseCurrency,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 175,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OVERHEAD_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $debitNote1->addLineItem($lineItem);

        $debitNote1->post();

        factory(Assignment::class)->create([
            'transaction_id' => $debitNote1->id,
            'cleared_id' => $supplierBill1->id,
            "amount" => 85,
        ]);

        // Foreign currency Supplier Bill Transaction
        $supplierBill2 = new SupplierBill([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => $rate->id,
            "currency_id" => $rate->currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 300,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null,
                "currency_id" => $rate->currency->id,
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $supplierBill2->addLineItem($lineItem);

        $supplierBill2->post();

        // Foreign currency Debit Note Transaction
        $debitNote2 = new DebitNote([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => $rate->id,
            "currency_id" => $rate->currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 175,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OVERHEAD_EXPENSE,
                'category_id' => null,
                "currency_id" => $rate->currency->id,
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $debitNote2->addLineItem($lineItem);

        $debitNote2->post();

        factory(Assignment::class)->create([
            'transaction_id' => $debitNote2->id,
            'cleared_id' => $supplierBill2->id,
            "amount" => 85,
        ]);

        // All transactions
        $schedule = new AccountSchedule($account->id);
        $transactions = $schedule->getTransactions();

        $this->assertEquals($transactions[0]->id, $balance1->id);
        $this->assertEquals($transactions[0]->transactionType, Transaction::getType($balance1->transaction_type));
        $this->assertEquals($transactions[0]->originalAmount, 60);
        $this->assertEquals($transactions[0]->amountCleared, 24);
        $this->assertEquals($transactions[0]->unclearedAmount, 36);

        $this->assertEquals($transactions[1]->id, $balance2->id);
        $this->assertEquals($transactions[1]->transactionType, Transaction::getType($balance2->transaction_type));
        $this->assertEquals($transactions[1]->originalAmount, 6300);
        $this->assertEquals($transactions[1]->amountCleared, 2520);
        $this->assertEquals($transactions[1]->unclearedAmount, 3780);

        $this->assertEquals($schedule->transactions[2]->id, $supplierBill1->id);
        $this->assertEquals($schedule->transactions[2]->transactionType, "Supplier Bill");
        $this->assertEquals($schedule->transactions[2]->originalAmount, 348);
        $this->assertEquals($schedule->transactions[2]->amountCleared, 85);
        $this->assertEquals($schedule->transactions[2]->unclearedAmount, 263);

        $this->assertEquals($schedule->transactions[3]->id, $supplierBill2->id);
        $this->assertEquals($schedule->transactions[3]->transactionType, "Supplier Bill");
        $this->assertEquals($schedule->transactions[3]->originalAmount, 36540);
        $this->assertEquals($schedule->transactions[3]->amountCleared, 8925);
        $this->assertEquals($schedule->transactions[3]->unclearedAmount, 27615);

        $this->assertEquals($schedule->balances['originalAmount'], 43248.0);
        $this->assertEquals($schedule->balances['amountCleared'], 11554);
        $this->assertEquals($schedule->balances['unclearedAmount'], 31694.0);
        $this->assertEquals($schedule->balances['totalAge'], 730);
        $this->assertEquals($schedule->balances['averageAge'], 183.0);

        // Base Currency transactions
        $schedule = new AccountSchedule($account->id, $baseCurrency);
        $transactions = $schedule->getTransactions();

        $this->assertEquals($transactions[0]->id, $balance1->id);
        $this->assertEquals($transactions[0]->transactionType, Transaction::getType($balance1->transaction_type));
        $this->assertEquals($transactions[0]->originalAmount, 60);
        $this->assertEquals($transactions[0]->amountCleared, 24);
        $this->assertEquals($transactions[0]->unclearedAmount, 36);

        $this->assertEquals($schedule->transactions[1]->id, $supplierBill1->id);
        $this->assertEquals($schedule->transactions[1]->transactionType, "Supplier Bill");
        $this->assertEquals($schedule->transactions[1]->originalAmount, 348);
        $this->assertEquals($schedule->transactions[1]->amountCleared, 85);
        $this->assertEquals($schedule->transactions[1]->unclearedAmount, 263);

        $this->assertEquals($schedule->balances['originalAmount'], 408.0);
        $this->assertEquals($schedule->balances['amountCleared'], 109);
        $this->assertEquals($schedule->balances['unclearedAmount'], 299.0);
        $this->assertEquals($schedule->balances['totalAge'], 365);
        $this->assertEquals($schedule->balances['averageAge'], 183.0);

        // Foreign Currency transactions
        $schedule = new AccountSchedule($account->id, $rate->currency_id);
        $transactions = $schedule->getTransactions();

        $this->assertEquals($transactions[0]->id, $balance2->id);
        $this->assertEquals($transactions[0]->transactionType, Transaction::getType($balance2->transaction_type));
        $this->assertEquals($transactions[0]->originalAmount, 60);
        $this->assertEquals($transactions[0]->amountCleared, 24);
        $this->assertEquals($transactions[0]->unclearedAmount, 36);

        $this->assertEquals($schedule->transactions[1]->id, $supplierBill2->id);
        $this->assertEquals($schedule->transactions[1]->transactionType, "Supplier Bill");
        $this->assertEquals($schedule->transactions[1]->originalAmount, 348);
        $this->assertEquals($schedule->transactions[1]->amountCleared, 85);
        $this->assertEquals($schedule->transactions[1]->unclearedAmount, 263);

        $this->assertEquals($schedule->balances['originalAmount'], 408.0);
        $this->assertEquals($schedule->balances['amountCleared'], 109);
        $this->assertEquals($schedule->balances['unclearedAmount'], 299.0);
        $this->assertEquals($schedule->balances['totalAge'], 365);
        $this->assertEquals($schedule->balances['averageAge'], 183.0);

    }
}
