<?php

namespace Tests\Feature;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Vat;
use IFRS\Reports\BalanceSheet;

use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\CashSale;
use IFRS\Transactions\JournalEntry;

class BalanceSheetTest extends TestCase
{
    /**
     * Test Balance Sheet
     *
     * @return void
     */
    public function testBalanceSheet()
    {
        $balanceSheet = new BalanceSheet();
        $balanceSheet->attributes();

        factory(Balance::class)->create([

            "account_id" => factory(Account::class)->create([
                "account_type" => Account::INVENTORY,
                'category_id' => null
            ])->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 100
        ]);

        factory(Balance::class)->create([
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::CURRENT_LIABILITY,
                'category_id' => null
            ])->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 100
        ]);

        $bill = new SupplierBill([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem =  factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);

        $bill->addLineItem($lineItem);
        $bill->post();

        $cashSale = new CashSale([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem =  factory(LineItem::class)->create([
            "amount" => 200,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);

        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::EQUITY,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 70,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $startDate = ReportingPeriod::periodStart();
        $endDate = ReportingPeriod::periodEnd();

        // dd(Account::sectionBalances([Account::INVENTORY], $startDate, $endDate));
        $balanceSheet->getSections($startDate, $endDate);
        $balanceSheet->toString();

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
            $balanceSheet->balances[$liabilities][Account::CONTROL],
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
            $balanceSheet->balances[$equity][BalanceSheet::NET_PROFIT],
            -200
        );

        $this->assertEquals(
            $balanceSheet->balances[$reconciliation][Account::RECONCILIATION],
            -70
        );
    }
}
