<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Reports;

use Illuminate\Support\Facades\Auth;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Entity;
use Ekmungai\IFRS\Models\ReportingPeriod;

/**
 *
 * @author emung
 *
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
     * @param int $multiplier
     * @param string $indent
     *
     * @return array[string, float]
     */
    protected function printSection(string $section, string $statement, int $multiplier, string $indent)
    {
        $statements = config('ifrs')['statements'];

        // Add Income Statement as Account Type for Balance Sheet
        $incomeStatement = [IncomeStatement::TITLE =>config('ifrs')['statements'][IncomeStatement::TITLE]];
        $accounts = array_merge(config('ifrs')['accounts'], $incomeStatement);

        $statement .=PHP_EOL;
        $statement .= $statements[$section].PHP_EOL;

        $total = 0;
        foreach (array_keys($this->balances[$section]) as $name) {
            $statement .= $indent.$accounts[$name].$indent;
            $statement .= $indent.($this->balances[$section][$name] * $multiplier).PHP_EOL;
            $total += $this->balances[$section][$name] * $multiplier;
        }

        return [$statement, $total];
    }

    /**
     * Constract Financial Statement for the given period
     *
     * @param string $year
     */
    public function __construct(string $year = null)
    {
        $this->entity = Auth::user()->entity;
        $this->reportingPeriod = is_null($year)? (string)ReportingPeriod::year():$year;
    }

    /**
     * Get Statement Reporting Period
     *
     * @return string
     */
    public function getReportingPeriod()
    {
        return $this->reportingPeriod;
    }

    /**
     * Set Statement Reporting period
     *
     * @param string $reportingPeriod
     */
    public function setReportingPeriod(string $reportingPeriod) : void
    {
        $this->reportingPeriod = $reportingPeriod;
    }

    /**
     * Get Statements Entity.
     *
     * @return \Ekmungai\IFRS\Models\Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set Statement Entity.
     *
     * @param \Ekmungai\IFRS\Models\Entity $entity
     */
    public function setEntity(Entity $entity) : void
    {
        $this->entity = $entity;
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
    public function getSections() : void
    {
        foreach (array_keys($this->accounts) as $section) {
            foreach (array_keys(config('ifrs')[$section]) as $accountType) {
                $accounts = Account::sectionBalances($accountType);

                if ($accounts["sectionTotal"] <> 0) {
                    $this->accounts[$section][$accountType] = $accounts["sectionCategories"];
                    $this->balances[$section][$accountType] = $accounts["sectionTotal"];

                    if ($accounts["sectionTotal"] < 0) {
                        $this->balances["credit"] += abs($accounts["sectionTotal"]);
                    } else {
                        $this->balances["debit"] += abs($accounts["sectionTotal"]);
                    }
                }
            }
        }
    }
}
