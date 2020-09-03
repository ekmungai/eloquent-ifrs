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
    const START_CASH_BALANCE = 'START_CASH_BALANCE';
    const END_CASH_BALANCE = 'END_CASH_BALANCE';

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
        $this->balances[self::PROFIT] = 0;
        $this->balances[self::START_CASH_BALANCE] = 0;
        $this->balances[self::END_CASH_BALANCE] = 0;
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
     * Get Cash Flow Statement Sections.
     */
    public function getSections(): void
    {
        // Accounts movements for the Period
        foreach (array_keys($this->balances) as $section) {
            switch ($section) {

                case self::PROFIT:
                    // Profit for the Period
                    $this->balances[self::PROFIT] = Account::sectionBalances(
                        IncomeStatement::getAccountTypes()
                    )["sectionTotal"] * -1;
                    break;

                case self::START_CASH_BALANCE:
                    // Cash at start of the Period
                    $periodStart = ReportingPeriod::periodStart($this->period['endDate']);

                    $this->balances[self::START_CASH_BALANCE] = Account::sectionBalances(
                        [Account::BANK],
                        $periodStart,
                        $this->period['startDate']
                    )["sectionTotal"];
                    break;

                case self::END_CASH_BALANCE:
                    // Cash at end of the Period
                    $this->balances[self::END_CASH_BALANCE] =  Account::sectionBalances(
                        [Account::BANK],
                        $this->period['startDate'],
                        $this->period['endDate']
                    )["sectionTotal"];
                    break;

                default:
                    $this->balances[$section] = Account::movement(array_keys(config('ifrs')[$section]));
            }
        }
    }

    /**
     * Print Cash Flow Statement.
     *
     * @codeCoverageIgnore
     */
    public function toString(): void
    {
        $statements = config('ifrs')['statements'];
        $statement = "";
        $indent = "    ";
        $separator = "                        ---------------";

        // Title
        $statement .= $this->entity->name . PHP_EOL;
        $statement .= config('ifrs')['statements'][self::TITLE] . PHP_EOL;
        $statement .= "For the Period: ";
        $statement .= $this->period['startDate']->format('M d Y');
        $statement .= " to " . $this->period['endDate']->format('M d Y') . PHP_EOL;

        // Operating Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Operating Cash Flow" . PHP_EOL;
        $statement .= $this->printSection(self::PROFIT, "", 1, $indent)[0];
        $statement .= $this->printSection(self::PROVISIONS, "", 1, $indent)[0];
        $statement .= $this->printSection(self::RECEIVABLES, "", 1, $indent)[0];
        $statement .= $this->printSection(self::PAYABLES, "", 1, $indent)[0];
        $statement .= $this->printSection(self::CURRENT_ASSETS, "", 1, $indent)[0];
        $statement .= $this->printSection(self::CURRENT_LIABILITIES, "", 1, $indent)[0];

        $totalOperationsCashFlow = $this->balances[self::PROFIT] + $this->balances[self::PROVISIONS] + $this->balances[self::RECEIVABLES] + $this->balances[self::PAYABLES] + $this->balances[self::TAXATION] + $this->balances[self::CURRENT_ASSETS] + $this->balances[self::CURRENT_LIABILITIES];

        $statement .= $separator . PHP_EOL;
        $statement .= "Total Operations Cash Flow ";
        $statement .= $indent . $totalOperationsCashFlow . PHP_EOL;

        // Investment Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Investment Cash Flow" . PHP_EOL;
        $statement .= $this->printSection(self::NON_CURRENT_ASSETS, "", 1, $indent)[0];

        $totalInvestmentCashFlow = $this->balances[self::NON_CURRENT_ASSETS];

        $statement .= $separator . PHP_EOL;
        $statement .= "Total Investment Cash Flow ";
        $statement .= $indent . $totalInvestmentCashFlow . PHP_EOL;

        // Financing Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Financing Cash Flow" . PHP_EOL;
        $statement .= $this->printSection(self::NON_CURRENT_LIABILITIES, "", 1, $indent)[0];
        $statement .= $this->printSection(self::EQUITY, "", 1, $indent)[0];

        $totalFinancingCashFlow = $this->balances[self::NON_CURRENT_LIABILITIES] + $this->balances[self::EQUITY];

        $statement .= $separator . PHP_EOL;
        $statement .= "Total Financing Cash Flow ";
        $statement .= $indent . $totalFinancingCashFlow . PHP_EOL;


        // Net Cash Flow
        $statement .= PHP_EOL;
        $statement .= "Net Cash Flow" . PHP_EOL;
        $statement .= $indent . $statements[self::START_CASH_BALANCE] . $indent;
        $statement .= $indent . $this->balances[self::START_CASH_BALANCE] . PHP_EOL;
        $statement .= $indent . "Cash Flow for the Period" . $indent;
        $statement .= $indent . ($totalFinancingCashFlow + $totalInvestmentCashFlow + $totalOperationsCashFlow) . PHP_EOL;
        $statement .= $separator . PHP_EOL;
        $statement .= $indent . $statements[self::END_CASH_BALANCE] . $indent;
        $statement .= $indent . $this->balances[self::START_CASH_BALANCE] + $totalFinancingCashFlow + $totalInvestmentCashFlow + $totalOperationsCashFlow . PHP_EOL;
        $statement .= "                        ================" . PHP_EOL;

        // Balance as per Cashbook
        $statement .= PHP_EOL;
        $statement .= "Balance as per Cashbook" . PHP_EOL;
        $statement .= $separator . PHP_EOL;
        $statement .= $indent . "Cashbook Balance" . $indent;
        $statement .= $indent . $this->balances[self::END_CASH_BALANCE] . PHP_EOL;
        $statement .= "                        ================" . PHP_EOL;

        print($statement);
    }

    /**
     * Print Statement Section
     *
     * @param string $section
     * @param string $statement
     * @param int    $multiplier
     * @param string $indent
     *
     * @return array[string, float]
     *
     * @codeCoverageIgnore
     */
    protected function printSection(string $section, string $statement, int $multiplier, string $indent)
    {
        $statements = config('ifrs')['statements'];


        $statement .= $indent . $statements[$section] . $indent;
        $statement .= $indent . $this->balances[$section] . PHP_EOL;

        return [$statement, 0];
    }
}
