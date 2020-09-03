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
     * Financial Statement Balances.
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

        // Add Income Statement as Account Type for Balance Sheet/Cash Flow Statement
        $incomeStatement = [IncomeStatement::TITLE => config('ifrs')['statements'][IncomeStatement::TITLE]];
        $account_names = array_merge(config('ifrs')['accounts'], $incomeStatement);

        $statement .= PHP_EOL;
        $statement .= $statements[$section] . PHP_EOL;

        $total = 0;
        foreach (array_keys($this->balances[$section]) as $name) {
            $statement .= $indent . $account_names[$name] . $indent;
            $statement .= $indent . ($this->balances[$section][$name] * $multiplier) . PHP_EOL;
            $total += $this->balances[$section][$name] * $multiplier;
        }

        return [$statement, $total];
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
            foreach (array_keys(config('ifrs')[$section]) as $accountType) {
                $account_names = Account::sectionBalances([$accountType]);

                if ($account_names["sectionTotal"] <> 0) {
                    $this->accounts[$section][$accountType] = $account_names["sectionCategories"];
                    $this->balances[$section][$accountType] = $account_names["sectionTotal"];

                    if ($account_names["sectionTotal"] < 0) {
                        $this->balances["credit"] += abs($account_names["sectionTotal"]);
                    } else {
                        $this->balances["debit"] += abs($account_names["sectionTotal"]);
                    }
                }
            }
        }
    }
}
