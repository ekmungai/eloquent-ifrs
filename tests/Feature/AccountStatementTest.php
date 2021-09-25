<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\Currency;
use IFRS\Models\Vat;

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
        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
            'category_id' => null
        ]);

        //opening balances
        factory(Balance::class, 3)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "currency_id" => $account->currency_id,
            "balance" => 50
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "currency_id" => $account->currency_id,
            "balance" => 40
        ]);

        //Cash Sale Transaction
        $cashSale = new CashSale([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);
        $cashSale->save();

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
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
        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        //Credit Contra Entry Transaction
        $creditContraEntry = new ContraEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $account->currency_id,
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "account_id" => $account->id,
            "quantity" => 1,
        ]);
        $creditContraEntry->addLineItem($lineItem);

        $creditContraEntry->post();

        //Debit Contra Entry Transaction
        $debitContraEntry = new ContraEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK,
                'category_id' => null,
                'currency_id' => $account->currency_id,
            ])->id,
            "quantity" => 1,
        ]);
        $debitContraEntry->addLineItem($lineItem);

        $debitContraEntry->post();

        //Client Receipt Transaction
        $clientReceipt = new ClientReceipt([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => $account->id,
            "quantity" => 1,
        ]);
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();

        //Cash Purchase Transaction
        $cashPurchase = new CashPurchase([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 75,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OTHER_EXPENSE,
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
        $cashPurchase->addLineItem($lineItem);

        $cashPurchase->post();

        //Supplier Payment Transaction
        $supplierPayment = new SupplierPayment([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            'currency_id' => $account->currency_id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "account_id" => $account->id,
            "quantity" => 1,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "quantity" => 1,
        ]);
        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        //Debit Joutnal Entry Transaction
        $debitJournalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $account->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "account_id" => $account->id,
            "quantity" => 1,
        ]);
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        $statement = new AccountStatement($account->id);
        $transactions = $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], 70);

        $this->assertEquals($transactions[0]->id, $cashSale->id);
        $this->assertEquals($transactions[0]->debit, $cashSale->amount);
        $this->assertEquals($transactions[0]->balance, 186);

        $this->assertEquals($transactions[1]->id, $creditContraEntry->id);
        $this->assertEquals($transactions[1]->credit, $creditContraEntry->amount);
        $this->assertEquals($transactions[1]->balance, 136);

        $this->assertEquals($transactions[2]->id, $debitContraEntry->id);
        $this->assertEquals($transactions[2]->debit, $debitContraEntry->amount);
        $this->assertEquals($transactions[2]->balance, 186);

        $this->assertEquals($transactions[3]->id, $clientReceipt->id);
        $this->assertEquals($transactions[3]->debit, $clientReceipt->amount);
        $this->assertEquals($transactions[3]->balance, 286);

        $this->assertEquals($transactions[4]->id, $cashPurchase->id);
        $this->assertEquals($transactions[4]->credit, $cashPurchase->amount);
        $this->assertEquals($transactions[4]->balance, 199);

        $this->assertEquals($transactions[5]->id, $supplierPayment->id);
        $this->assertEquals($transactions[5]->credit, $supplierPayment->amount);
        $this->assertEquals($transactions[5]->balance, 149);

        $this->assertEquals($transactions[6]->id, $creditJournalEntry->id);
        $this->assertEquals($transactions[6]->credit, $creditJournalEntry->amount);
        $this->assertEquals($transactions[6]->balance, 99);

        $this->assertEquals($transactions[7]->id, $debitJournalEntry->id);
        $this->assertEquals($transactions[7]->debit, $debitJournalEntry->amount);
        $this->assertEquals($transactions[7]->balance, 149);

        $this->assertEquals($statement->balances['closing'], 149);
    }

    /**
     * Test Client Account AccountStatement
     *
     * @return void
     */
    public function testClientAccountAccountStatement()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);

        //opening balances
        factory(Balance::class, 3)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 50
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 40,
        ]);

        //Client Invoice Transaction
        $clientInvoice = new ClientInvoice([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
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
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        //Credit Note Transaction
        $creditNote = new CreditNote([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
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

        //Client Receipt Transaction
        $currency = factory(Currency::class)->create();
        $clientReceipt = new ClientReceipt([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create(
            [
                "amount" => 100,
                "account_id" => factory(Account::class)->create([
                    "account_type" => Account::BANK,
                    'category_id' => null,
                    'currency_id' => $currency->id,
                ])->id,
                "quantity" => 1,
            ]
        );
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "quantity" => 1,
        ]);
        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create(
            [
                "amount" => 50,
                "account_id" => $account->id,
                "quantity" => 1,
            ]
        );
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        $statement = new AccountStatement($account->id);
        $transactions = $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], 70);

        $this->assertEquals($transactions[0]->id, $clientInvoice->id);
        $this->assertEquals($transactions[0]->debit, $clientInvoice->amount);
        $this->assertEquals($transactions[0]->balance, 186);

        $this->assertEquals($transactions[1]->id, $creditNote->id);
        $this->assertEquals($transactions[1]->credit, $creditNote->amount);
        $this->assertEquals($transactions[1]->balance, 128);

        $this->assertEquals($transactions[2]->id, $clientReceipt->id);
        $this->assertEquals($transactions[2]->credit, $clientReceipt->amount);
        $this->assertEquals($transactions[2]->balance, 28);

        $this->assertEquals($transactions[3]->id, $creditJournalEntry->id);
        $this->assertEquals($transactions[3]->credit, $creditJournalEntry->amount);
        $this->assertEquals($transactions[3]->balance, -22);

        $this->assertEquals($transactions[4]->id, $debitJournalEntry->id);
        $this->assertEquals($transactions[4]->debit, $debitJournalEntry->amount);
        $this->assertEquals($transactions[4]->balance, 28);

        $this->assertEquals($statement->balances['closing'], 28);
    }

    /**
     * Test Supplier Account AccountStatement
     *
     * @return void
     */
    public function testSupplierAccountAccountStatement()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null
        ]);

        //opening balances
        factory(Balance::class, 3)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 50
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 40
        ]);

        //Supplier Bill Transaction
        $supplierBill = new SupplierBill([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_EXPENSE,
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
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
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

        //Supplier Payment Transaction
        $currency = factory(Currency::class)->create();
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

        //Credit Journal Entry Transaction
        $creditJournalEntry = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "quantity" => 1,
        ]);
        $creditJournalEntry->addLineItem($lineItem);

        $creditJournalEntry->post();

        //Debit Journal Entry Transaction
        $debitJournalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "account_id" => $account->id,
            "quantity" => 1,
        ]);
        $debitJournalEntry->addLineItem($lineItem);

        $debitJournalEntry->post();

        $statement = new AccountStatement($account->id);
        $transactions = $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], -70);

        $this->assertEquals($transactions[0]->id, $supplierBill->id);
        $this->assertEquals($transactions[0]->credit, $supplierBill->amount);
        $this->assertEquals($transactions[0]->balance, -186);

        $this->assertEquals($transactions[1]->id, $debitNote->id);
        $this->assertEquals($transactions[1]->debit, $debitNote->amount);
        $this->assertEquals($transactions[1]->balance, -128);

        $this->assertEquals($transactions[2]->id, $supplierPayment->id);
        $this->assertEquals($transactions[2]->debit, $supplierPayment->amount);
        $this->assertEquals($transactions[2]->balance, -28);

        $this->assertEquals($transactions[3]->id, $creditJournalEntry->id);
        $this->assertEquals($transactions[3]->credit, $creditJournalEntry->amount);
        $this->assertEquals($transactions[3]->balance, -78);

        $this->assertEquals($transactions[4]->id, $debitJournalEntry->id);
        $this->assertEquals($transactions[4]->debit, $debitJournalEntry->amount);
        $this->assertEquals($transactions[4]->balance, -28);

        $this->assertEquals($statement->balances['closing'], -28);
    }

    /**
     * Test AccountStatement Currency filters
     *
     * @return void
     */
    public function testAccountStatementCurrencyFilters()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);

        $rate = factory(ExchangeRate::class)->create([
            'rate' => 105
        ]);

        $baseCurrency = Auth::user()->entity->currency_id;

        // base currency opening balances
        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
                "currency_id" => $baseCurrency,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "currency_id" => $baseCurrency,
            "balance" => 50
        ]);

        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
                "currency_id" => $baseCurrency,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "currency_id" => $baseCurrency,
            "balance" => 40,
        ]);

        // Foreign currency opening balances
        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => $rate->id,
            "currency_id" => $rate->currency_id,
            'reporting_period_id' => $this->period->id,
            "balance" => 40
        ]);

        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => $rate->id,
            "currency_id" => $rate->currency_id,
            'reporting_period_id' => $this->period->id,
            "balance" => 50,
        ]);

        // Base currency debit transaction
        $clientInvoice1 = new ClientInvoice([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
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
        $clientInvoice1->addLineItem($lineItem);

        $clientInvoice1->post();     

        // Foreign currency debit transaction
        $clientInvoice2 = new ClientInvoice([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $rate->currency_id,
            "exchange_rate_id" => $rate->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
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
        $clientInvoice2->addLineItem($lineItem);

        $clientInvoice2->post();

        // Base currency credit transaction
        $clientReceipt1 = new ClientReceipt([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $baseCurrency,
        ]);

        $lineItem = factory(LineItem::class)->create(
            [
                "amount" => 100,
                "account_id" => factory(Account::class)->create([
                    "account_type" => Account::BANK,
                    'category_id' => null,
                    "currency_id" => $baseCurrency,
                ])->id,
                "quantity" => 1,
            ]
        );
        $clientReceipt1->addLineItem($lineItem);

        $clientReceipt1->post();

        // Foreign currency credit transaction
        $clientReceipt2 = new ClientReceipt([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "currency_id" => $rate->currency_id,
            "exchange_rate_id" => $rate->id,
        ]);

        $lineItem = factory(LineItem::class)->create(
            [
                "amount" => 100,
                "account_id" => factory(Account::class)->create([
                    "account_type" => Account::BANK,
                    'category_id' => null,
                    "currency_id" => $rate->currency_id,
                ])->id,
                "quantity" => 1,
            ]
        );
        $clientReceipt2->addLineItem($lineItem);

        $clientReceipt2->post();

        // All transactions
        $statement = new AccountStatement($account->id);
        $transactions = $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], -1040);

        $this->assertEquals($transactions[0]->id, $clientInvoice1->id);
        $this->assertEquals($transactions[0]->debit, $clientInvoice1->amount);
        $this->assertEquals($transactions[0]->balance, -924);

        $this->assertEquals($transactions[1]->id, $clientInvoice2->id);
        $this->assertEquals($transactions[1]->debit, $clientInvoice2->amount * $rate->rate);
        $this->assertEquals($transactions[1]->balance, 11256);

        $this->assertEquals($transactions[2]->id, $clientReceipt1->id);
        $this->assertEquals($transactions[2]->credit, $clientReceipt1->amount);
        $this->assertEquals($transactions[2]->balance, 11156);

        $this->assertEquals($transactions[3]->id, $clientReceipt2->id);
        $this->assertEquals($transactions[3]->credit, $clientReceipt2->amount * $rate->rate);
        $this->assertEquals($transactions[3]->balance, 656);

        $this->assertEquals($statement->balances['closing'], 656.0);

        // Base Currency transactions
        $statement = new AccountStatement($account->id, $baseCurrency);
        $transactions = $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], 10);

        $this->assertEquals($transactions[0]->id, $clientInvoice1->id);
        $this->assertEquals($transactions[0]->debit, $clientInvoice1->amount);
        $this->assertEquals($transactions[0]->balance, 126);

        $this->assertEquals($transactions[1]->id, $clientReceipt1->id);
        $this->assertEquals($transactions[1]->credit, $clientReceipt1->amount);
        $this->assertEquals($transactions[1]->balance, 26);

        $this->assertEquals($statement->balances['closing'], 26);

        // Foreign Currency transactions
        $statement = new AccountStatement($account->id, $rate->currency_id);
        $transactions = $statement->getTransactions();

        $this->assertEquals($statement->balances['opening'], -10);

        $this->assertEquals($transactions[0]->id, $clientInvoice2->id);
        $this->assertEquals($transactions[0]->debit, $clientInvoice2->amount);
        $this->assertEquals($transactions[0]->balance, 106);

        $this->assertEquals($transactions[1]->id, $clientReceipt2->id);
        $this->assertEquals($transactions[1]->credit, $clientReceipt2->amount);
        $this->assertEquals($transactions[1]->balance, 6);

        $this->assertEquals($statement->balances['closing'], 6);
    }
}
