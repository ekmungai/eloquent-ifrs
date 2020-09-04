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

class CashFlowStatement extends FinancialStatement
{

    /**
     * Cash Flow Statement Title
     *
     * @var string
     */
    const TITLE = 'CASH_FLOW_STATEMENT';

    /**
     * Cash Flow Statement Sections
     *
     * @var string
     */
    const PROVISIONS = 'PROVISIONS';
    const RECEIVABLES = 'RECEIVABLES';
    const PAYABLES = 'PAYABLES';
    const CURRENT_ASSETS = 'CURRENT_ASSETS';
    const CURRENT_LIABILITIES = 'CURRENT_LIABILITIES';
    const TAXATION = 'TAXATION';
    const NON_CURRENT_ASSETS = 'NON_CURRENT_ASSETS';
    const NON_CURRENT_LIABILITIES = 'NON_CURRENT_LIABILITIES';
    const EQUITY = 'EQUITY';
    const PROFIT = 'PROFIT';
    const OPERATIONS_CASH_FLOW = 'OPERATIONS_CASH_FLOW';
    const INVESTMENT_CASH_FLOW = 'INVESTMENT_CASH_FLOW';
    const FINANCING_CASH_FLOW = 'FINANCING_CASH_FLOW';
    const START_CASH_BALANCE = 'START_CASH_BALANCE';
    const END_CASH_BALANCE = 'END_CASH_BALANCE';
    const NET_CASH_FLOW = 'NET_CASH_FLOW';
    const CASHBOOK_BALANCE = 'CASHBOOK_BALANCE';

    /**
     * Cash Flow Statement period.
     *
     * @var array
     */
    public $period = [
        "startDate" => null,
        "endDate" => null
    ];

    /**
     * Cash Flow Statement Account Balance Movements.
     *
     * @var array
     */
    public $balances = [];

    /**
     * Construct Cash Flow Statement for the given period.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function __construct(string $startDate = null, string $endDate = null)
    {
        $this->period['startDate'] = is_null($startDate) ? ReportingPeriod::periodStart() : Carbon::parse($startDate);
        $this->period['endDate'] = is_null($endDate) ? Carbon::now() : Carbon::parse($endDate);
        $this->period['endDate']->addDay();

        $period = ReportingPeriod::where("year", $endDate)->first();
        parent::__construct($period);

        $this->result_indents = 1;

        // Section Balances
        $this->balances[self::PROVISIONS] = 0;
        $this->balances[self::RECEIVABLES] = 0;
        $this->balances[self::PAYABLES] = 0;
        $this->balances[self::TAXATION] = 0;
        $this->balances[self::CURRENT_ASSETS] = 0;
        $this->balances[self::CURRENT_LIABILITIES] = 0;
        $this->balances[self::NON_CURRENT_ASSETS] = 0;
        $this->balances[self::NON_CURRENT_LIABILITIES] = 0;
        $this->balances[self::EQUITY] = 0;

        // Statement Results
        $this->results[self::OPERATIONS_CASH_FLOW] = 0;
        $this->results[self::INVESTMENT_CASH_FLOW] = 0;
        $this->results[self::FINANCING_CASH_FLOW] = 0;
        $this->results[self::CASHBOOK_BALANCE] = 0;
        $this->results[self::END_CASH_BALANCE] = 0;
    }

    /**
     * Cash Flow Statement attributes.
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
        // Accounts movements for the Period
        foreach (array_keys($this->balances) as $section) {
            $this->balances[$section] = Account::movement(config('ifrs')[$section]);
        }

        // Profit for the Period
        $this->balances[self::PROFIT] = Account::sectionBalances(
            IncomeStatement::getAccountTypes()
        )["sectionTotal"] * -1;

        // Operations Cash Flow
        $this->results[self::OPERATIONS_CASH_FLOW] = $this->balances[self::PROFIT] + array_sum(array_slice($this->balances, 0, 6));

        // Investment Cash Flow
        $this->results[self::INVESTMENT_CASH_FLOW] = $this->balances[self::NON_CURRENT_ASSETS];

        // Financing Cash Flow
        $this->results[self::FINANCING_CASH_FLOW] = $this->balances[self::NON_CURRENT_LIABILITIES] + $this->balances[self::EQUITY];

        // Net Cash Flow
        $this->balances[self::NET_CASH_FLOW] = $this->results[self::OPERATIONS_CASH_FLOW] + $this->results[self::INVESTMENT_CASH_FLOW] + $this->results[self::FINANCING_CASH_FLOW];

        // Cash at start of the Period
        $periodStart = ReportingPeriod::periodStart($this->period['endDate']);
        $this->balances[self::START_CASH_BALANCE] = Account::sectionBalances(
            [Account::BANK],
            $periodStart,
            $this->period['startDate']
        )["sectionTotal"];

        // Cash at end of the Period
        $this->results[self::END_CASH_BALANCE] =  $this->balances[self::START_CASH_BALANCE] + $this->balances[self::NET_CASH_FLOW];

        // Cashbook Balance
        $this->results[self::CASHBOOK_BALANCE] =  Account::sectionBalances(
            [Account::BANK],
            $this->period['startDate'],
            $this->period['endDate']
        )["sectionTotal"];
    }

    /**
     * Print Cash Flow Statement.
     *
     * @codeCoverageIgnore
     */
    public function toString(): void
    {

        $statement = $this->statement;
        // Title
        $statement = $this->printTitle($statement, self::TITLE);

        // Operating Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Operating Cash Flow" . PHP_EOL;

        $statement = $this->printSection(self::PROFIT, $statement, $this->indent);
        $statement = $this->printSection(self::PROVISIONS, $statement, $this->indent);
        $statement = $this->printSection(self::RECEIVABLES, $statement, $this->indent);
        $statement = $this->printSection(self::PAYABLES, $statement, $this->indent);
        $statement = $this->printSection(self::TAXATION, $statement, $this->indent);
        $statement = $this->printSection(self::CURRENT_ASSETS, $statement, $this->indent);
        $statement = $this->printSection(self::CURRENT_LIABILITIES, $statement, $this->indent);

        // Total Operating Cash Flow
        $statement = $this->printResult(self::OPERATIONS_CASH_FLOW, $statement, $this->indent, $this->result_indents);

        // Investment Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Investment Cash Flow" . PHP_EOL;
        $statement = $this->printSection(self::NON_CURRENT_ASSETS, $statement, $this->indent);

        // Total Investment Cash Flow
        $statement = $this->printResult(self::INVESTMENT_CASH_FLOW, $statement, $this->indent, $this->result_indents);

        // Financing Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Financing Cash Flow" . PHP_EOL;
        $statement = $this->printSection(self::NON_CURRENT_LIABILITIES, $statement, $this->indent);
        $statement = $this->printSection(self::EQUITY, $statement, $this->indent);

        // Total Financing Cash Flow
        $statement = $this->printResult(self::FINANCING_CASH_FLOW, $statement, $this->indent, $this->result_indents);

        // Net Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Net Cash Flow" . PHP_EOL;
        $statement = $this->printSection(self::START_CASH_BALANCE, $statement, $this->indent);
        $statement = $this->printSection(self::NET_CASH_FLOW, $statement, $this->indent, $this->result_indents);
        $statement = $this->printResult(self::END_CASH_BALANCE, $statement, $this->indent, $this->result_indents);
        $statement .= $this->grand_total . PHP_EOL;

        // Cashbook Balance
        $statement .= PHP_EOL;
        $statement = $this->printResult(self::CASHBOOK_BALANCE, $statement, $this->indent, $this->result_indents);
        $statement .= $this->grand_total . PHP_EOL;

        print($statement);
    }

    /**
     * Print Statement Section
     *
     * @param string $section
     * @param string $statement
     * @param string $indent
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function printSection(string $section, string $statement, string $indent)
    {
        $sectionNames = config('ifrs')['statements'];

        $statement .= $indent . $sectionNames[$section] . $indent;

        return $statement . $indent . $this->balances[$section] . PHP_EOL;
    }
}
