<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Tests\TestCase;

use App\Models\Account;
use App\Models\Balance;
use App\Models\LineItem;

use App\Reports\BalanceSheet;
use App\Reports\IncomeStatement;

use App\Transactions\SupplierBill;
use App\Transactions\CashSale;
use App\Transactions\JournalEntry;

class BalanceSheetTest extends TestCase
{
    /**
     * Test Income Statement
     *
     * @return void
     */
    public function testBalanceSheet()
    {
        $balanceSheet = new BalanceSheet();

        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => factory('App\Models\Account')->create([
                "account_type" => Account::INVENTORY
            ])->id,
            "balance_type" => Balance::D,
            "exchange_rate_id" => factory('App\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        factory(Balance::class)->create([
            "year" => date("Y"),
            "account_id" => factory('App\Models\Account')->create([
                "account_type" => Account::CURRENT_LIABILITY
            ])->id,
            "balance_type" => Balance::C,
            "exchange_rate_id" => factory('App\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 100
        ]);

        $bill = SupplierBill::new(
            factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem =  LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::NON_CURRENT_ASSET
            ]),
            factory('App\Models\Vat')->create(["rate" => 16]),
            100,
            1,
            null,
            factory('App\Models\Account')->create([
                "account_type" => Account::CONTROL_ACCOUNT
            ])
        );
        $bill->addLineItem($lineItem);
        $bill->post();

        $cashSale = CashSale::new(
            factory(Account::class)->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem =  LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::OPERATING_REVENUE
            ]),
            factory('App\Models\Vat')->create(["rate" => 16]),
            200,
            1,
            null,
            factory('App\Models\Account')->create([
                "account_type" => Account::CONTROL_ACCOUNT
            ])
        );
        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        $journalEntry = JournalEntry::new(
            factory(Account::class)->create([
                'account_type' => Account::EQUITY,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ]),
            factory('App\Models\Vat')->create(["rate" => 0]),
            70
        );
        $journalEntry->setCredited(false);
        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $balanceSheet->getSections();

        $assets = BalanceSheet::ASSETS;
        $liabilities = BalanceSheet::LIABILITIES;
        $reconciliation = BalanceSheet::RECONCILIATION;
        $equity = BalanceSheet::EQUITY;

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::INVENTORY],
            100
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::BANK],
            232
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::NON_CURRENT_ASSET],
            100
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::CONTROL_ACCOUNT],
            -16
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::CURRENT_LIABILITY],
            -100
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::PAYABLE],
            -116
        );

        $this->assertEquals(
            $balanceSheet->balances[$equity][Account::EQUITY],
            70
        );

        $this->assertEquals(
            $balanceSheet->balances[$equity][IncomeStatement::TITLE],
            200
        );

        $this->assertEquals(
            $balanceSheet->balances[$reconciliation][Account::RECONCILIATION],
            -70
        );
    }
}
