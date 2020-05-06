<?php

namespace Tests\Feature;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\LineItem;

use IFRS\Reports\BalanceSheet;
use IFRS\Reports\IncomeStatement;

use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\CashSale;
use IFRS\Transactions\JournalEntry;

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
        $balanceSheet->attributes();

        factory(Balance::class)->create(
            [
            "year" => date("Y"),
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::INVENTORY
                ]
            )->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory('IFRS\Models\ExchangeRate')->create(
                [
                "rate" => 1
                ]
            )->id,
            "amount" => 100
            ]
        );

        factory(Balance::class)->create(
            [
            "year" => date("Y"),
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::CURRENT_LIABILITY
                ]
            )->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory('IFRS\Models\ExchangeRate')->create(
                [
                "rate" => 1
                ]
            )->id,
            "amount" => 100
            ]
        );

        $bill = new SupplierBill(
            [
            "account_id" => factory(Account::class)->create(
                [
                'account_type' => Account::PAYABLE,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem =  factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::NON_CURRENT_ASSET
                ]
            )->id,
            "vat_account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::CONTROL_ACCOUNT
                ]
            )->id,
            ]
        );

        $bill->addLineItem($lineItem);
        $bill->post();

        $cashSale = new CashSale(
            [
            "account_id" => factory(Account::class)->create(
                [
                'account_type' => Account::BANK,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem =  factory(LineItem::class)->create(
            [
            "amount" => 200,
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
            "vat_account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::CONTROL_ACCOUNT
                ]
            )->id,
            ]
        );

        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        $journalEntry = new JournalEntry(
            [
            "account_id" => factory(Account::class)->create(
                [
                'account_type' => Account::EQUITY,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 70,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 0
                ]
            )->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::RECONCILIATION
                ]
            )->id,
            ]
        );
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
