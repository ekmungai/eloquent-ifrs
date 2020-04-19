<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Reports;

use Carbon\Carbon;

use Ekmungai\IFRS\Models\ReportingPeriod;

class BalanceSheet extends FinancialStatement
{
    /**
     * Balance Sheet Sections
     *
     * @var string
     */

    const TITLE = 'BALANCE_SHEET';
    const ASSETS = 'ASSETS';
    const LIABILITIES = 'LIABILITIES';
    const EQUITY = 'EQUITY';
    const RECONCILIATION = 'RECONCILIATION';

    /**
     * Balance Sheet period.
     *
     * @var array
     */
    public $period = [
        "endDate" => null
    ];


    /**
     * Construct Balance Sheet as at the given end date
     *
     * @param string $endDate
     */
    public function __construct(string $endDate = null)
    {
        $this->period['endDate'] = is_null($endDate)? Carbon::now(): Carbon::parse($endDate);

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
    public function getSections() : void
    {
        parent::getSections();

        // Income Statement Balance
        $incomeStatement = new IncomeStatement(null, $this->period['endDate']);
        $incomeStatement->getSections();

        $credit = $incomeStatement->balances['credit'];
        $debit = $incomeStatement->balances['debit'];

        $this->balances["credit"] += $credit;
        $this->balances["debit"] += $debit;

        $this->balances[self::EQUITY][IncomeStatement::TITLE] = $credit - $debit;
    }

    /**
     * Print Balance Sheet.
     *
     * @codeCoverageIgnore
     */
    public function toString() : void
    {
        $indent = "    ";
        $separator = "                        ---------------";

        // Title
        $statement = $this->entity->name.PHP_EOL;
        $statement .= config('ifrs')['statements'][self::TITLE].PHP_EOL;
        $statement .= _("As at: ");
        $statement .= $this->period['endDate']->format('M d Y').PHP_EOL;

        // Asset Accounts
        $assets = $this->printSection(self::ASSETS, $statement, 1, $indent);
        $statement = $assets[0];

        $statement .= $separator.PHP_EOL;
        $statement .= _("Total Assets         ");
        $statement .= $indent.($assets[1]).PHP_EOL;

        // Liability Accounts
        $liabilities = $this->printSection(self::LIABILITIES, $statement, -1, $indent);
        $statement = $liabilities[0];

        $statement .= $separator.PHP_EOL;
        $statement .= _("Total Liabilities    ");
        $statement .= $indent.($liabilities[1]).PHP_EOL;

        // Reconciliation Accounts
        $reconciliation = $this->printSection(self::RECONCILIATION, $statement, 1, $indent);
        $statement = $reconciliation[0];

        $statement .= $separator.PHP_EOL;
        $statement .= _("Total Reconciliation  ");
        $statement .= $indent.($reconciliation[1]).PHP_EOL;
        $statement .=PHP_EOL;

        $statement .= $separator.PHP_EOL;
        $statement .= _("Net Assets           ");
        $statement .= $indent.($assets[1] - $liabilities[1] - $reconciliation[1]).PHP_EOL;
        $statement .= str_replace("-", "=", $separator.PHP_EOL);

        // Equity Accounts
        $equity = $this->printSection(self::EQUITY, $statement, 1, $indent);
        $statement = $equity[0];

        $statement .= $separator.PHP_EOL;
        $statement .= _("Total Equity         ");
        $statement .= $indent.($equity[1]).PHP_EOL;
        $statement .= str_replace("-", "=", $separator.PHP_EOL);

        print($statement);
    }
}
