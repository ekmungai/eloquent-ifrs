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

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use IFRS\Models\Balance;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Entity;

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
     * Construct Income Statement for the given period.
     *
     * @param string $startDate
     * @param string $endDate
     * @param Entity $entity
     */
    public function __construct(string $startDate = null, string $endDate = null, Entity $entity = null)
    {
        $this->period['startDate'] = is_null($startDate) ? ReportingPeriod::periodStart(null, $entity) : Carbon::parse($startDate);
        $this->period['endDate'] = is_null($endDate) ? ReportingPeriod::periodEnd(null, $entity) : Carbon::parse($endDate);

        $reportingPeriod = ReportingPeriod::getPeriod($endDate, $entity);
        parent::__construct($reportingPeriod, $entity);

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
        $this->results[self::NET_PROFIT] = 0;

        // Statement Totals
        $this->totals[self::OPERATING_REVENUES] = 0;
        $this->totals[self::NON_OPERATING_REVENUES] = 0;
        $this->totals[self::OPERATING_EXPENSES] = 0;
        $this->totals[self::NON_OPERATING_EXPENSES] = 0;
    }

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
     * Get Income Statement Account Types.
     *
     * @param int|string month
     * @param int|string year
     * @return array
     */
    public static function getResults($month, $year, Entity $entity = null)
    {
        if (is_null($entity)) {
            $entity = Auth::user()->entity;
        }

        $startDate = Carbon::parse($year . '-' . $month . '-01')->startOfDay();
        $endDate = Carbon::parse($year . '-' . $month . '-01')->endOfMonth();

        $revenues = self::getBalance(config('ifrs')[self::OPERATING_REVENUES], $startDate, $endDate, $entity);

        $otherRevenues = self::getBalance(config('ifrs')[self::NON_OPERATING_REVENUES], $startDate, $endDate, $entity);

        $cogs = self::getBalance(config('ifrs')[self::OPERATING_EXPENSES], $startDate, $endDate, $entity);

        $expenses = self::getBalance(config('ifrs')[self::NON_OPERATING_EXPENSES], $startDate, $endDate, $entity);

        return [
            self::OPERATING_REVENUES => abs($revenues),
            self::NON_OPERATING_REVENUES => abs($otherRevenues),
            self::OPERATING_EXPENSES => $cogs,
            self::GROSS_PROFIT => abs($revenues + $otherRevenues + $cogs),
            self::NON_OPERATING_EXPENSES => $expenses,
            self::NET_PROFIT => abs($revenues + $otherRevenues + $cogs + $expenses),
        ];
    }

    /**
     * Get Income Statement Account Type balance total.
     *
     * @param int month
     * @param int year
     */
    private static function getBalance(array $accountTypes, Carbon $startDate, Carbon $endDate, Entity $entity = null): float
    {
        if (is_null($entity)) {
            $entity = Auth::user()->entity;
        }

        $accountTable = config('ifrs.table_prefix') . 'accounts';
        $ledgerTable = config('ifrs.table_prefix') . 'ledgers';

        $baseQuery = DB::table(
            $accountTable
        )
            ->leftJoin($ledgerTable, $accountTable . '.id', '=', $ledgerTable . '.post_account')
            ->whereIn('account_type', $accountTypes)
            ->where($ledgerTable . '.deleted_at', null)
            ->where($accountTable . '.entity_id', $entity->id)
            ->where($ledgerTable . '.posting_date', '>=', $startDate)
            ->where($ledgerTable . '.posting_date', '<=', $endDate->endOfDay());

        $cloneQuery = clone $baseQuery;

        $debits = $baseQuery
            ->where($ledgerTable . '.entry_type', Balance::DEBIT)
            ->sum($ledgerTable . '.amount');

        $credits = $cloneQuery
            ->where($ledgerTable . '.entry_type', Balance::CREDIT)
            ->sum($ledgerTable . '.amount');

        return $debits - $credits;
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
    public function getSections($startDate = null, $endDate = null, $fullbalance = true): array
    {

        parent::getSections($this->period['startDate'], $this->period['endDate'], false);

        // Gross Profit
        $this->results[self::GROSS_PROFIT] = ($this->totals[self::OPERATING_REVENUES] + $this->totals[self::OPERATING_EXPENSES]) * -1;

        // Total Revenue    
        $this->results[self::TOTAL_REVENUE] = $this->results[self::GROSS_PROFIT] + $this->totals[self::NON_OPERATING_REVENUES] * -1;

        // Net Profit
        $this->results[self::NET_PROFIT] = $this->results[self::TOTAL_REVENUE] - $this->totals[self::NON_OPERATING_EXPENSES];

        return [
            "accounts" => $this->accounts,
            "balances" => $this->balances,
            "results" => $this->results,
            "totals" => $this->totals,
        ];
    }

    /**
     * Print Income Statement.
     *
     * @codeCoverageIgnore
     */
    public function toString(): void
    {
        $statement = $this->statement;

        // Title
        $statement = $this->printTitle($statement, self::TITLE);

        // Operating Revenues
        $statement = $this->printSection(self::OPERATING_REVENUES, $statement, $this->indent, -1);

        // Operating Expenses
        $statement = $this->printSection(self::OPERATING_EXPENSES, $statement, $this->indent);

        // Gross Profit
        $statement = $this->printResult(self::GROSS_PROFIT, $statement, $this->indent, $this->result_indents);

        // Non Operating Revenue
        $statement = $this->printSection(self::NON_OPERATING_REVENUES, $statement, $this->indent, -1);

        // Total Revenue
        $statement = $this->printResult(self::TOTAL_REVENUE, $statement, $this->indent, $this->result_indents);

        // Non Operating Expenses
        $statement = $this->printSection(self::NON_OPERATING_EXPENSES, $statement, $this->indent);

        // Total Expenses
        $statement = $this->printTotal(self::NON_OPERATING_EXPENSES, $statement, $this->indent, 1);

        // Net Profit
        $statement = $this->printResult(self::NET_PROFIT, $statement, $this->indent, $this->result_indents);
        $statement .= $this->grand_total . PHP_EOL;

        print($statement);
    }
}
