<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Reports;

use Illuminate\Support\Facades\Auth;

use IFRS\Models\Account;
use IFRS\Models\Entity;
use IFRS\Models\ReportingPeriod;

/**
 *
 * @author emung
 */
abstract class FinancialStatement
{

    /**
     * Financial Statement Entity.
     *
     * @var Entity
     */
    protected $entity;

    /**
     * Financial Statement Reporting Period.
     *
     * @var string
     */
    public $reportingPeriod = null;

    /**
     * Financial Statement balances.
     *
     * @var array
     */
    public $balances = [
        "debit" => 0,
        "credit" => 0,
    ];

    /**
     * Financial Statement accounts.
     *
     * @var array
     */
    public $accounts = [];

    /**
     * Financial Statement totals.
     *
     * @var array
     */
    public $totals = [];

    /**
     * Financial Statement results.
     *
     * @var array
     */
    public $results = [];

    /**
     * Print Statement Title
     *
     * @param string $statement
     * @param string $title
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function printTitle(string $statement, string $title)
    {
        $dateFormat = 'M d Y';
        $statement .= PHP_EOL;
        $statement .= $this->entity->name . PHP_EOL;
        $statement .= config('ifrs')['statements'][$title] . PHP_EOL;

        $period = in_array(
            'startDate',
            array_keys($this->period)
        ) ? "For the Period:" . $this->period['startDate']->format(
            $dateFormat
        ) . " to " . $this->period['endDate']->format(
            $dateFormat
        ) . PHP_EOL : "As at: " . $this->period['endDate']->format(
            $dateFormat
        ) . PHP_EOL;

        return $statement .= $period;
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
    protected function printSection(string $section, string $statement, string $indent, int $amountFactor = 1)
    {
        $accountNames = array_merge(config('ifrs')['accounts'], config('ifrs')['statements']);

        $statement .= PHP_EOL;
        $statement .= $accountNames[$section] . PHP_EOL;

        foreach (array_keys($this->balances[$section]) as $name) {
            $statement .= $indent . $accountNames[$name] . $indent;
            $statement .= $indent . $this->balances[$section][$name] * $amountFactor . PHP_EOL;
        }

        return $statement;
    }

    /**
     * Print Statement Total
     *
     * @param string $statement
     * @param string $section
     * @param string $indent
     * @param int $indentFactor
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function printTotal(string $section, string $statement, string $indent, int $amountFactor = 1, int $indentFactor = 1)
    {
        $accountNames = array_merge(config('ifrs')['accounts'], config('ifrs')['statements']);

        $statement .= $this->separator . PHP_EOL;
        $statement .= 'Total ' . $accountNames[$section]  . str_repeat($indent, $indentFactor);

        return $statement .= $this->totals[$section] * $amountFactor . PHP_EOL;
    }

    /**
     * Print Statement Result
     *
     * @param string $statement
     * @param string $result
     * @param string $indent
     * @param int $indentFactor
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function printResult(string $result, string $statement, string $indent, int $indentFactor)
    {
        $statement .= $this->separator . PHP_EOL;
        $statement .= config('ifrs')['statements'][$result] . str_repeat($indent, $indentFactor);

        return $statement .= $this->results[$result] . PHP_EOL;
    }

    /**
     * Construct Financial Statement for the given period
     *
     * @param string $year
     */
    public function __construct(string $year = null)
    {
        $this->entity = Auth::user()->entity;
        $this->reportingPeriod = is_null($year) ? (string) ReportingPeriod::year() : $year;

        $this->statement = "";
        $this->indent = "    ";
        $this->separator = "                        ---------------";
        $this->grand_total = "                        ===============";
        $this->result_indents = 4;
    }

    /**
     * Print Financial Statement attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            "Entity" => $this->entity->name,
            "ReportingPeriod" => $this->reportingPeriod,
            "Balances" => $this->balances
        ];
    }

    /**
     * Get Statement Sections.
     */
    public function getSections(): void
    {
        foreach (array_keys($this->accounts) as $section) {
            foreach (config('ifrs')[$section] as $accountType) {
                $sectionBalances = Account::sectionBalances([$accountType]);

                if ($sectionBalances["sectionClosingBalance"] <> 0) {

                    $this->accounts[$section][$accountType] = $sectionBalances["sectionCategories"];
                    $this->balances[$section][$accountType] = $sectionBalances["sectionClosingBalance"];
                    $this->totals[$section] += $sectionBalances["sectionClosingBalance"];

                    if ($sectionBalances["sectionClosingBalance"] < 0) {
                        $this->balances["credit"] += abs($sectionBalances["sectionClosingBalance"]);
                    } else {
                        $this->balances["debit"] += abs($sectionBalances["sectionClosingBalance"]);
                    }
                }
            }
        }
    }
}
