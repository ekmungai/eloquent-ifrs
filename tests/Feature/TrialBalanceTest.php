<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\LineItem;

use Ekmungai\IFRS\Reports\BalanceSheet;
use Ekmungai\IFRS\Reports\IncomeStatement;
use Ekmungai\IFRS\Reports\TrialBalance;

use Ekmungai\IFRS\Transactions\JournalEntry;
use Ekmungai\IFRS\Transactions\SupplierBill;
use Ekmungai\IFRS\Transactions\CashPurchase;
use Ekmungai\IFRS\Transactions\ContraEntry;
use Ekmungai\IFRS\Transactions\ClientInvoice;

class TrialBalanceTest extends TestCase
{
    /**
     * Test Trial Balance
     *
     * @return void
     */
    public function testTrialBalance()
    {
        /*
         |
         | Opening balances must be made to balance manually, Transactions enforce double entry
         |

         | ------------------------------
         | Balance Sheet: Assets Accounts
         | ------------------------------
         */

        $nonCurrentAsset = factory(Account::class)->create([
            'account_type' => Account::NON_CURRENT_ASSET,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $nonCurrentAsset,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $bill = SupplierBill::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $bill->addLineItem(
            factory(LineItem::class)->create(["account_id" => $nonCurrentAsset])
        );
        $bill->post();


        $contraAsset = factory(Account::class)->create([
            'account_type' => Account::CONTRA_ASSET,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $contraAsset,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $journal = JournalEntry::new($contraAsset, Carbon::now(), $this->faker->word);

        $journal->addLineItem(factory(LineItem::class)->create());
        $journal->post();


        $inventory = factory(Account::class)->create([
            'account_type' => Account::INVENTORY,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $inventory,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $cashPurchase = CashPurchase::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $cashPurchase->addLineItem(factory(LineItem::class)->create(["account_id" => $inventory]));
        $cashPurchase->post();


        $bank = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $bank,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $contraEntry = ContraEntry::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $contraEntry->addLineItem(factory(LineItem::class)->create([
            "account_id" => $bank,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
        ]));
        $contraEntry->post();


        $currentAsset = factory(Account::class)->create([
            'account_type' => Account::CURRENT_ASSET,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $currentAsset,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $journalEntry = JournalEntry::new($currentAsset, Carbon::now(), $this->faker->word);

        $journalEntry->addLineItem(factory(LineItem::class)->create());
        $journalEntry->post();


        $receivable = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $receivable,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $clientInvoice = ClientInvoice::new($receivable, Carbon::now(), $this->faker->word);

        $clientInvoice->addLineItem(factory(LineItem::class)->create([
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::OPERATING_REVENUE,
            ])->id,
        ]));
        $clientInvoice->post();

        /*
         | ------------------------------
         | Balance Sheet: Liability Accounts
         | ------------------------------
         */

        $nonCurrentLiability = factory(Account::class)->create([
            'account_type' => Account::NON_CURRENT_LIABILITY,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $nonCurrentLiability,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $journalEntry = JournalEntry::new($nonCurrentLiability, Carbon::now(), $this->faker->word);

        $journalEntry->addLineItem(factory(LineItem::class)->create());
        $journalEntry->post();


        $controlAccount = factory(Account::class)->create([
            'account_type' => Account::CONTROL_ACCOUNT,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $controlAccount,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $journalEntry = JournalEntry::new($controlAccount, Carbon::now(), $this->faker->word);

        $journalEntry->addLineItem(factory(LineItem::class)->create());
        $journalEntry->post();


        $currentLiability = factory(Account::class)->create([
            'account_type' => Account::CURRENT_LIABILITY,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $currentLiability,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $journalEntry = JournalEntry::new($currentLiability, Carbon::now(), $this->faker->word);

        $journalEntry->addLineItem(factory(LineItem::class)->create());
        $journalEntry->post();


        $payable = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $payable,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $bill = SupplierBill::new($payable, Carbon::now(), $this->faker->word);

        $bill->addLineItem(factory(LineItem::class)->create(["account_id" => $nonCurrentAsset]));
        $bill->post();


        $reconciliation = factory(Account::class)->create([
            'account_type' => Account::RECONCILIATION,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $reconciliation,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $journalEntry = JournalEntry::new($reconciliation, Carbon::now(), $this->faker->word);

        $journalEntry->addLineItem(factory(LineItem::class)->create());
        $journalEntry->post();

        /*
         | ------------------------------
         | Balance Sheet: Equity Accounts
         | ------------------------------
         */

        $equity = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
        ]);

        //balance
        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => $equity,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        //transaction
        $journalEntry = JournalEntry::new($equity, Carbon::now(), $this->faker->word);

        $journalEntry->addLineItem(factory(LineItem::class)->create());
        $journalEntry->post();

        /*
         | ------------------------------
         | Income Statement: Operating Accounts
         | ------------------------------
         */

        $operatingIncome = factory(Account::class)->create([
            'account_type' => Account::OPERATING_REVENUE,
         ]);

        //transaction
        $clientInvoice = ClientInvoice::new($receivable, Carbon::now(), $this->faker->word);

        $clientInvoice->addLineItem(factory(LineItem::class)->create([
            "account_id" => $operatingIncome->id,
        ]));
        $clientInvoice->post();

        $operatingExpenses = factory(Account::class)->create([
            'account_type' => Account::OPERATING_EXPENSE,
         ]);

        //transaction
        $cashPurchase = CashPurchase::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $cashPurchase->addLineItem(factory(LineItem::class)->create(["account_id" => $operatingExpenses]));
        $cashPurchase->post();

        /*
         | ------------------------------
         | Income Statement: Non Operating Accounts
         | ------------------------------
         */

        $nonOperatingRevenue = factory(Account::class)->create([
           'account_type' => Account::NON_OPERATING_REVENUE,
        ]);

        //transaction
        $journalEntry = JournalEntry::new($nonOperatingRevenue, Carbon::now(), $this->faker->word);

        $journalEntry->addLineItem(factory(LineItem::class)->create());
        $journalEntry->post();

        $directExpense = factory(Account::class)->create([
            'account_type' => Account::DIRECT_EXPENSE,
        ]);

        //transaction
        $bill = SupplierBill::new($payable, Carbon::now(), $this->faker->word);

        $bill->addLineItem(factory(LineItem::class)->create(["account_id" => $directExpense]));
        $bill->post();

        $overheadExpense = factory(Account::class)->create([
            'account_type' => Account::OVERHEAD_EXPENSE,
        ]);

        //transaction
        $cashPurchase = CashPurchase::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $cashPurchase->addLineItem(factory(LineItem::class)->create(["account_id" => $overheadExpense]));
        $cashPurchase->post();

        $otherExpense = factory(Account::class)->create([
            'account_type' => Account::OTHER_EXPENSE,
        ]);

        //transaction
        $bill = SupplierBill::new($payable, Carbon::now(), $this->faker->word);

        $bill->addLineItem(factory(LineItem::class)->create(["account_id" => $otherExpense]));
        $bill->post();

        $trialBalance = new TrialBalance();
        $trialBalance->getSections();

        /*
         | ------------------------------
         | Balancing
         | ------------------------------
         */

        $this->assertEquals(
            round($trialBalance->balances['debit']),
            round($trialBalance->balances['credit'])
        );
        /*
         | ------------------------------
         | Balance Sheet: Assets Accounts
         | ------------------------------
         */

        $bsAccounts = $trialBalance->accounts[BalanceSheet::TITLE];
        $this->assertTrue(
            $bsAccounts[Account::NON_CURRENT_ASSET]["accounts"]->contains(
                function ($item, $key) use ($nonCurrentAsset) {
                    return $item->id == $nonCurrentAsset->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::CONTRA_ASSET]["accounts"]->contains(
                function ($item, $key) use ($contraAsset) {
                    return $item->id == $contraAsset->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::INVENTORY]["accounts"]->contains(
                function ($item, $key) use ($inventory) {
                    return $item->id == $inventory->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::BANK]["accounts"]->contains(
                function ($item, $key) use ($bank) {
                    return $item->id == $bank->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::CURRENT_ASSET]["accounts"]->contains(
                function ($item, $key) use ($currentAsset) {
                    return $item->id == $currentAsset->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::RECEIVABLE]["accounts"]->contains(
                function ($item, $key) use ($receivable) {
                    return $item->id == $receivable->id;
                }
            )
        );

        /*
         | ------------------------------
         | Balance Sheet: Liability Accounts
         | ------------------------------
         */
        $this->assertTrue(
            $bsAccounts[Account::NON_CURRENT_LIABILITY]["accounts"]->contains(
                function ($item, $key) use ($nonCurrentLiability) {
                    return $item->id == $nonCurrentLiability->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::CONTROL_ACCOUNT]["accounts"]->contains(
                function ($item, $key) use ($controlAccount) {
                    return $item->id == $controlAccount->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::CURRENT_LIABILITY]["accounts"]->contains(
                function ($item, $key) use ($currentLiability) {
                    return $item->id == $currentLiability->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::PAYABLE]["accounts"]->contains(
                function ($item, $key) use ($payable) {
                    return $item->id == $payable->id;
                }
            )
        );

        $this->assertTrue(
            $bsAccounts[Account::RECONCILIATION]["accounts"]->contains(
                function ($item, $key) use ($reconciliation) {
                    return $item->id == $reconciliation->id;
                }
            )
        );

        /*
         | ------------------------------
         | Balance Sheet: Equity Accounts
         | ------------------------------
         */

        $this->assertTrue(
            $bsAccounts[Account::EQUITY]["accounts"]->contains(
                function ($item, $key) use ($equity) {
                    return $item->id == $equity->id;
                }
            )
        );

        /*
         | ------------------------------
         | Income Statement: Operating Accounts
         | ------------------------------
         */
        $isAccounts = $trialBalance->accounts[IncomeStatement::TITLE];

        $this->assertTrue(
            $isAccounts[Account::OPERATING_REVENUE]["accounts"]->contains(
                function ($item, $key) use ($operatingIncome) {
                    return $item->id == $operatingIncome->id;
                }
            )
        );

        $this->assertTrue(
            $isAccounts[Account::OPERATING_EXPENSE]["accounts"]->contains(
                function ($item, $key) use ($operatingExpenses) {
                    return $item->id == $operatingExpenses->id;
                }
            )
        );

        /*
         | ------------------------------
         | Income Statement: Non Operating Accounts
         | ------------------------------
         */

        $this->assertTrue(
            $isAccounts[Account::NON_OPERATING_REVENUE]["accounts"]->contains(
                function ($item, $key) use ($nonOperatingRevenue) {
                    return $item->id == $nonOperatingRevenue->id;
                }
            )
        );

        $this->assertTrue(
            $isAccounts[Account::DIRECT_EXPENSE]["accounts"]->contains(
                function ($item, $key) use ($directExpense) {
                    return $item->id == $directExpense->id;
                }
            )
        );

        $this->assertTrue(
            $isAccounts[Account::OVERHEAD_EXPENSE]["accounts"]->contains(
                function ($item, $key) use ($overheadExpense) {
                    return $item->id == $overheadExpense->id;
                }
            )
        );

        $this->assertTrue(
            $isAccounts[Account::OTHER_EXPENSE]["accounts"]->contains(
                function ($item, $key) use ($otherExpense) {
                    return $item->id == $otherExpense->id;
                }
            )
        );
    }
}
