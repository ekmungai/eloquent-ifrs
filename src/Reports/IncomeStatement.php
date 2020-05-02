<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Reports;

use Carbon\Carbon;

use IFRS\Models\ReportingPeriod;

class IncomeStatement extends FinancialStatement
{
    /**
     * Income Statement Sections
     *
     * @var string
     */

    const TITLE = 'INCOME_STATEMENT';
    const OPERATING_REVENUES = 'OPERATING_REVENUES';
    const NON_OPERATING_REVENUES = 'NON_OPERATING_REVENUES';
    const OPERATING_EXPENSES = 'OPERATING_EXPENSES';
    const NON_OPERATING_EXPENSES = 'NON_OPERATING_EXPENSES';

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
     * Construct Income Statement for the given period.
     *
     * @param string $startDate
     * @param string $endDate
     *
     */
    public function __construct(string $startDate = null, string $endDate = null)
    {
        $this->period['startDate'] = is_null($startDate)? ReportingPeriod::periodStart(): $startDate;
        $this->period['endDate'] = is_null($endDate)? Carbon::now(): Carbon::parse($endDate);

        $period = ReportingPeriod::where("year", $endDate)->first();
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
     * Print Income Statement.
     *
     * @codeCoverageIgnore
     */
    public function toString() : void
    {
        $statement ="";
        $indent = "    ";
        $separator = "                        ---------------";

        // Title
        $statement .= $this->entity->name.PHP_EOL;
        $statement .= config('ifrs')['statements'][self::TITLE].PHP_EOL;
        $statement .= _("For the Period: ");
        $statement .= $this->period['startDate']->format('M d Y');
        $statement .= _(" to ").$this->period['endDate']->format('M d Y').PHP_EOL;

        // Operating Revenues
        $opRevenue = $this->printSection(self::OPERATING_REVENUES, $statement, -1, $indent);
        $statement = $opRevenue[0];

        // Operating Expenses
        $opExpenses = $this->printSection(self::OPERATING_EXPENSES, $statement, 1, $indent);
        $statement = $opExpenses[0];

        $statement .= $separator.PHP_EOL;
        $statement .= _("Operations Gross Profit ");
        $statement .= ($opRevenue[1] - $opExpenses[1]).PHP_EOL;

        // Non Operating Revenue
        $nOpRevenue = $this->printSection(self::NON_OPERATING_REVENUES, $statement, -1, $indent);
        $statement = $nOpRevenue[0];

        $statement .= $separator.PHP_EOL;
        $statement .= _("Total Revenue       ");
        $statement .= $indent.($opRevenue[1] - $opExpenses[1] + $nOpRevenue[1]).PHP_EOL;

        // Non Operating Expenses
        $nOpExpense = $this->printSection(self::NON_OPERATING_EXPENSES, $statement, 1, $indent);
        $statement = $nOpExpense[0];
        $statement .=PHP_EOL;

        $statement .= $separator.PHP_EOL;
        $statement .= _("Total Expenses       ");
        $statement .= $indent.($nOpExpense[1]).PHP_EOL;
        $statement .=PHP_EOL;

        $statement .= $separator.PHP_EOL;
        $statement .= _("Net Profit          ");
        $statement .= $indent.($opRevenue[1] - $opExpenses[1] + $nOpExpense[1]).PHP_EOL;
        $statement .= str_replace("-", "=", $separator.PHP_EOL);

        print($statement);
    }
}
