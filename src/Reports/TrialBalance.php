<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Reports;

use IFRS\Models\Account;

class TrialBalance extends FinancialStatement
{
    /** Construct Trial Balance
     *
     * @param string $year
     *
     */
    public function __construct(string $year = null)
    {
        parent::__construct($year);

        $this->accounts[IncomeStatement::TITLE] = [];
        $this->accounts[BalanceSheet::TITLE] = [];
    }

    /**
     * Get Trial Balance Sections.
     *
     */
    public function getSections() : void
    {
        foreach (Account::all() as $account) {
            $balance = $account->closingBalance($this->reportingPeriod);

            if ($balance > 0) {
                $this->balances["debit"] += abs($balance);
            } else {
                $this->balances["credit"] += abs($balance);
            }

            $this->getIncomeStatementSections($account, $balance);
            $this->getBalanceSheetSections($account, $balance);
        }
    }

    /**
     * Get Income Statement Sections.
     *
     * @param Account $account
     * @param float $balance
     */
    public function getIncomeStatementSections(Account $account, $balance) : void
    {
        $isAccounts = array_merge(
            array_keys(config('ifrs')[IncomeStatement::OPERATING_REVENUES]),
            array_keys(config('ifrs')[IncomeStatement::NON_OPERATING_REVENUES]),
            array_keys(config('ifrs')[IncomeStatement::OPERATING_EXPENSES]),
            array_keys(config('ifrs')[IncomeStatement::NON_OPERATING_EXPENSES])
        );

        if (in_array($account->account_type, $isAccounts)) {
            if (array_key_exists($account->account_type, $this->accounts[IncomeStatement::TITLE])) {
                $this->accounts[IncomeStatement::TITLE][$account->account_type]['accounts']->push($account->attributes());
                $this->accounts[IncomeStatement::TITLE][$account->account_type]['balance'] += abs($balance);
            } else {
                $this->accounts[IncomeStatement::TITLE][$account->account_type]['accounts'] = collect([$account->attributes()]);
                $this->accounts[IncomeStatement::TITLE][$account->account_type]['balance'] = abs($balance);
            }
        }
    }

    /**
     * Get Balance Sheet Sections.
     *
     * @param Account $account
     * @param float $balance
     */
    public function getBalanceSheetSections(Account $account, $balance) : void
    {
        $bsAccounts = array_merge(
            array_keys(config('ifrs')[BalanceSheet::ASSETS]),
            array_keys(config('ifrs')[BalanceSheet::LIABILITIES]),
            array_keys(config('ifrs')[BalanceSheet::EQUITY]),
            array_keys(config('ifrs')[BalanceSheet::RECONCILIATION])
        );

        if (in_array($account->account_type, $bsAccounts)) {
            if (array_key_exists($account->account_type, $this->accounts[BalanceSheet::TITLE])) {
                $this->accounts[BalanceSheet::TITLE][$account->account_type]['accounts']->push($account->attributes());
                $this->accounts[BalanceSheet::TITLE][$account->account_type]['balance'] += abs($balance);
            } else {
                $this->accounts[BalanceSheet::TITLE][$account->account_type]['accounts'] = collect([$account->attributes()]);
                $this->accounts[BalanceSheet::TITLE][$account->account_type]['balance'] = abs($balance);
            }
        }
    }
}
