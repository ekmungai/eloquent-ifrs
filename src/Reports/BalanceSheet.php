<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Reports;

use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\ReportingPeriod;

class BalanceSheet extends FinancialStatement
{
    /**
     * Balance Sheet Title
     *
     * @var string
     */
    const TITLE = 'BALANCE_SHEET';

    /**
     * Balance Sheet Sections
     *
     * @var string
     */
    const ASSETS = 'ASSETS';
    const LIABILITIES = 'LIABILITIES';
    const EQUITY = 'EQUITY';
    const RECONCILIATION = 'RECONCILIATION';
    const TOTAL_ASSETS = 'TOTAL_ASSETS';
    const TOTAL_LIABILITIES = 'TOTAL_LIABILITIES';
    const NET_ASSETS = 'NET_ASSETS';
    const TOTAL_RECONCILIATION = 'TOTAL_RECONCILIATION';
    const NET_PROFIT = 'NET_PROFIT';
    const TOTAL_EQUITY = 'TOTAL_EQUITY';

    /**
     * Balance Sheet period.
     *
     * @var array
     */
    public $period = [
        "endDate" => null
    ];

    /**
     * Get Balance Sheet Account Types.
     *
     * @return array
     */
    public static function getAccountTypes()
    {
        return array_merge(
            config('ifrs')[BalanceSheet::ASSETS],
            config('ifrs')[BalanceSheet::LIABILITIES],
            config('ifrs')[BalanceSheet::EQUITY],
            config('ifrs')[BalanceSheet::RECONCILIATION]
        );
    }

    /**
     * Construct Balance Sheet as at the given end date
     *
     * @param string $endDate
     */
    public function __construct(string $endDate = null)
    {
        $this->period['endDate'] = is_null($endDate) ? Carbon::now() : Carbon::parse($endDate);

        $period = ReportingPeriod::where("year", $endDate)->first();
        parent::__construct($period);

        // Section Accounts
        $this->accounts[self::ASSETS] = [];
        $this->accounts[self::LIABILITIES] = [];
        $this->accounts[self::EQUITY] = [];
        $this->accounts[self::RECONCILIATION] = [];

        // Section Balances
        $this->balances[self::ASSETS] = [];
        $this->balances[self::LIABILITIES] = [];
        $this->balances[self::EQUITY] = [];
        $this->balances[self::RECONCILIATION] = [];

        // Statement Results
        $this->results[self::TOTAL_ASSETS] = 0;
        $this->results[self::TOTAL_LIABILITIES] = 0;
        $this->results[self::NET_ASSETS] = 0;
        $this->results[self::TOTAL_EQUITY] = 0;
    }

    /**
     * Print Income Statement attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return array_merge($this->period, parent::attributes());
    }

    /**
     * Get Balance Sheet Sections.
     */
    public function getSections(): void
    {
        parent::getSections();

        // Total Assets
        $this->results[self::TOTAL_ASSETS] = array_sum($this->balances[self::ASSETS]);

        // Total Liabilities
        $this->results[self::TOTAL_LIABILITIES] = array_sum($this->balances[self::LIABILITIES]);

        // Total Reconciliation
        $this->results[self::TOTAL_RECONCILIATION] = array_sum($this->balances[self::RECONCILIATION]);

        // Net Assets
        $this->results[self::NET_ASSETS] = $this->results[self::TOTAL_ASSETS] - ($this->results[self::TOTAL_LIABILITIES] + $this->results[self::TOTAL_RECONCILIATION]);

        // Net Profit
        $this->balances[self::EQUITY][self::NET_PROFIT] =  Account::sectionBalances(
            IncomeStatement::getAccountTypes()
        )["sectionTotal"];

        // Total Equity
        $this->results[self::TOTAL_EQUITY] = abs(array_sum($this->balances[self::EQUITY]));
    }

    /**
     * Print Balance Sheet.
     *
     * @codeCoverageIgnore
     */
    public function toString(): void
    {
        $statement = $this->statement;

        // Title
        $statement = $this->printTitle($statement, self::TITLE);

        // Asset Accounts
        $statement = $this->printSection(self::ASSETS, $statement, $this->indent);

        // Total Assets
        $statement = $this->printResult(self::TOTAL_ASSETS, $statement, $this->indent, $this->result_indents);

        // Liability Accounts
        $statement = $this->printSection(self::LIABILITIES, $statement, $this->indent);

        // Total Liabilities
        $statement = $this->printResult(self::TOTAL_LIABILITIES, $statement, $this->indent, $this->result_indents);

        // Reconciliation Accounts
        $statement = $this->printSection(self::RECONCILIATION, $statement, $this->indent);

        // Total Reconciliation
        $statement = $this->printResult(self::TOTAL_RECONCILIATION, $statement, $this->indent, $this->result_indents);

        // Net Assets
        $statement = $this->printResult(self::NET_ASSETS, $statement, $this->indent, $this->result_indents);
        $statement .= $this->grand_total . PHP_EOL;

        // Equity
        $statement = $this->printSection(self::EQUITY, $statement, $this->indent);

        // Total Assets
        $statement = $this->printResult(self::TOTAL_EQUITY, $statement, $this->indent, $this->result_indents);
        $statement .= $this->grand_total . PHP_EOL;

        print($statement);
    }
}
