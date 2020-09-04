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

class IncomeStatement extends FinancialStatement
{

    /**
     * Income Statement Title
     *
     * @var string
     */
    const TITLE = 'INCOME_STATEMENT';

    /**
     * Income Statement Sections
     *
     * @var string
     */
    const OPERATING_REVENUES = 'OPERATING_REVENUES';
    const NON_OPERATING_REVENUES = 'NON_OPERATING_REVENUES';
    const OPERATING_EXPENSES = 'OPERATING_EXPENSES';
    const NON_OPERATING_EXPENSES = 'NON_OPERATING_EXPENSES';
    const GROSS_PROFIT = 'GROSS_PROFIT';
    const TOTAL_REVENUE = 'TOTAL_REVENUE';
    const TOTAL_EXPENSES = 'TOTAL_EXPENSES';
    const NET_PROFIT = 'NET_PROFIT';

    /**
     * Income Statement period.
     *
     * @var array
     */
    public $period = [
        "startDate" => null,
        "endDate" => null
    ];

    /**
     * Get Income Statement Account Types.
     *
     * @return array
     */
    public static function getAccountTypes()
    {
        return array_merge(
            config('ifrs')[self::OPERATING_REVENUES],
            config('ifrs')[self::NON_OPERATING_REVENUES],
            config('ifrs')[self::OPERATING_EXPENSES],
            config('ifrs')[self::NON_OPERATING_EXPENSES]
        );
    }

    /**
     * Construct Income Statement for the given period.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function __construct(string $startDate = null, string $endDate = null)
    {
        $this->period['startDate'] = is_null($startDate) ? ReportingPeriod::periodStart() : $startDate;
        $this->period['endDate'] = is_null($endDate) ? Carbon::now() : Carbon::parse($endDate);

        $period = ReportingPeriod::where("calendar_year", $endDate)->first();
        parent::__construct($period);

        // Section Accounts
        $this->accounts[self::OPERATING_REVENUES] = [];
        $this->accounts[self::NON_OPERATING_REVENUES] = [];
        $this->accounts[self::OPERATING_EXPENSES] = [];
        $this->accounts[self::NON_OPERATING_EXPENSES] = [];

        // Section Balances
        $this->balances[self::OPERATING_REVENUES] = [];
        $this->balances[self::NON_OPERATING_REVENUES] = [];
        $this->balances[self::OPERATING_EXPENSES] = [];
        $this->balances[self::NON_OPERATING_EXPENSES] = [];

        // Statement Results
        $this->results[self::GROSS_PROFIT] = 0;
        $this->results[self::TOTAL_REVENUE] = 0;
        $this->results[self::TOTAL_EXPENSES] = 0;
        $this->results[self::NET_PROFIT] = 0;
    }

    /**
     * Income Statement attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return array_merge($this->period, parent::attributes());
    }

    /**
     * Get Cash Flow Statement Sections and Results.
     */
    public function getSections(): void
    {
        parent::getSections();

        // Gross Profit
        $this->results[self::GROSS_PROFIT] = array_sum($this->balances[self::OPERATING_REVENUES]) - array_sum($this->balances[self::OPERATING_EXPENSES]);

        // Total Revenue
        $this->results[self::TOTAL_REVENUE] = $this->results[self::GROSS_PROFIT] + array_sum($this->balances[self::NON_OPERATING_REVENUES]);

        // Total Expenses
        $this->results[self::TOTAL_EXPENSES] = array_sum($this->balances[self::NON_OPERATING_EXPENSES]);

        // Net Profit
        $this->results[self::NET_PROFIT] = $this->results[self::TOTAL_REVENUE] - $this->results[self::TOTAL_EXPENSES];
    }

    /**
     * Print Income Statement.
     *
     * @codeCoverageIgnore
     */
    public function toString(): void
    {
        $this->getSections();

        $statement = $this->statement;

        // Title
        $statement = $this->printTitle($statement, self::TITLE);

        // Operating Revenues
        $statement = $this->printSection(self::OPERATING_REVENUES, $statement, $this->indent);

        // Operating Expenses
        $statement = $this->printSection(self::OPERATING_EXPENSES, $statement, $this->indent);

        // Gross Profit
        $statement = $this->printResult(self::GROSS_PROFIT, $statement, $this->indent, $this->result_indents);

        // Non Operating Revenue
        $statement = $this->printSection(self::NON_OPERATING_REVENUES, $statement, $this->indent);

        // Total Revenue
        $statement = $this->printResult(self::TOTAL_REVENUE, $statement, $this->indent, $this->result_indents);

        // Non Operating Expenses
        $statement = $this->printSection(self::NON_OPERATING_EXPENSES, $statement, $this->indent);

        // Total Expenses
        $statement = $this->printResult(self::TOTAL_EXPENSES, $statement, $this->indent, $this->result_indents);

        // Net Profit
        $statement = $this->printResult(self::NET_PROFIT, $statement, $this->indent, $this->result_indents);
        $statement .= $this->grand_total . PHP_EOL;

        print($statement);
    }
}
