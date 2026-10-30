<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\ClosingRate;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;

use IFRS\Reports\AccountSchedule;
use IFRS\Reports\AccountStatement;
use IFRS\Reports\BalanceSheet;
use IFRS\Reports\CashFlowStatement;
use IFRS\Reports\TrialBalance;

use IFRS\Transactions\ClientReceipt;
use IFRS\Transactions\JournalEntry;

class FinancialYearTest extends TestCase
{
    /**
     * Make the entity's financial year run from July to June.
     *
     * @return void
     */
    private function startYearInJuly(): void
    {
        $entity = Auth::user()->entity;
        $entity->year_start = 7;
        $entity->save();
    }

    /**
     * Create a Reporting Period for the Entity.
     *
     * @param int $year
     *
     * @return ReportingPeriod
     */
    private function period(int $year): ReportingPeriod
    {
        return factory(ReportingPeriod::class)->create([
            'calendar_year' => $year,
            'entity_id' => Auth::user()->entity->id,
        ]);
    }

    /**
     * Create an Account.
     *
     * @param string $type
     *
     * @return Account
     */
    private function account(string $type = Account::RECEIVABLE): Account
    {
        return factory(Account::class)->create([
            'account_type' => $type,
            'category_id' => null,
        ]);
    }

    /**
     * Create an opening Balance.
     *
     * @param Account $account
     * @param ReportingPeriod $period
     * @param string $date
     * @param string $type
     * @param float $amount
     *
     * @return void
     */
    private function openingBalance(Account $account, ReportingPeriod $period, string $date, string $type, float $amount): void
    {
        factory(Balance::class)->create([
            'account_id' => $account->id,
            'balance_type' => $type,
            'exchange_rate_id' => factory(ExchangeRate::class)->create(['rate' => 1])->id,
            'reporting_period_id' => $period->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => $date,
            'balance' => $amount,
        ]);
    }

    /**
     * Post a Journal Entry against the Account.
     *
     * @param Account $account
     * @param string $date
     * @param float $amount
     * @param bool $credited
     * @param string $lineAccountType
     *
     * @return void
     */
    private function postJournal(Account $account, string $date, float $amount, bool $credited, string $lineAccountType = Account::CONTROL): void
    {
        $journalEntry = new JournalEntry([
            'account_id' => $account->id,
            'transaction_date' => Carbon::parse($date),
            'narration' => $this->faker->word,
            'credited' => $credited,
            'currency_id' => $account->currency_id,
        ]);
        $journalEntry->save();

        $journalEntry->addLineItem(factory(LineItem::class)->create([
            'amount' => $amount,
            'quantity' => 1,
            'account_id' => factory(Account::class)->create([
                'account_type' => $lineAccountType,
                'category_id' => null,
                'currency_id' => $account->currency_id,
            ])->id,
        ]));
        $journalEntry->post();
    }

    /**
     * Test the first day of a financial year.
     *
     * @return void
     */
    public function testYearStart()
    {
        $this->assertEquals('2024-01-01', ReportingPeriod::yearStart(2024)->toDateString());

        $this->startYearInJuly();

        $this->assertEquals('2024-07-01', ReportingPeriod::yearStart(2024)->toDateString());
    }

    /**
     * Test the Account Statement of a year that does not start in January.
     *
     * @return void
     */
    public function testAccountStatementOpeningBalance()
    {
        $this->startYearInJuly();

        $this->period(2023);
        $period = $this->period(2024);
        $account = $this->account();

        $this->openingBalance($account, $period, '2024-07-01', Balance::CREDIT, 2000);
        $this->postJournal($account, '2024-10-05', 1000, false);
        $this->postJournal($account, '2024-10-10', 250, true);

        $statement = new AccountStatement($account->id, null, '2024-10-01', '2024-10-31');
        $transactions = $statement->getTransactions();

        $this->assertEquals(-2000, $statement->balances['opening']);
        $this->assertEquals(-1250, $statement->balances['closing']);
        $this->assertEquals(-1000, $transactions[0]->balance);
        $this->assertEquals(-1250, $transactions[1]->balance);
    }

    /**
     * Test the Account Statement does not need the Reporting Period of the
     * calendar year before the financial year.
     *
     * @return void
     */
    public function testAccountStatementWithoutPriorPeriod()
    {
        $this->startYearInJuly();

        $period = $this->period(2024);
        $account = $this->account();

        $this->openingBalance($account, $period, '2024-07-01', Balance::DEBIT, 2000);

        $statement = new AccountStatement($account->id, null, '2024-10-01', '2024-10-31');
        $statement->getTransactions();

        $this->assertEquals(2000, $statement->balances['opening']);
    }

    /**
     * Test the Account Statement opens with movements from before its start date,
     * and counts a transaction on the start date only once.
     *
     * @return void
     */
    public function testAccountStatementPriorMovements()
    {
        $period = $this->period(2024);
        $account = $this->account();

        $this->openingBalance($account, $period, '2024-01-01', Balance::DEBIT, 2000);
        $this->postJournal($account, '2024-03-05', 300, false);
        $this->postJournal($account, '2024-10-01', 50, false);
        $this->postJournal($account, '2024-10-05', 1000, false);

        $statement = new AccountStatement($account->id, null, '2024-10-01', '2024-10-31');
        $transactions = $statement->getTransactions();

        $this->assertCount(2, $transactions);
        $this->assertEquals(2300, $statement->balances['opening']);
        $this->assertEquals(3350, $statement->balances['closing']);
    }

    /**
     * Test Account balances of a year that does not start in January.
     *
     * @return void
     */
    public function testAccountBalances()
    {
        $this->startYearInJuly();

        $this->period(2023);
        $period = $this->period(2024);
        $account = $this->account();

        $this->openingBalance($account, $period, '2024-07-01', Balance::DEBIT, 2000);
        $this->postJournal($account, '2024-10-05', 500, false);

        $reportingCurrencyId = $account->entity->currency_id;

        $this->assertEquals(2000, $account->openingBalance(2024)[$reportingCurrencyId]);
        $this->assertEquals(2500, $account->closingBalance('2025-03-31')[$reportingCurrencyId]);
        $this->assertEquals(2000, Account::openingBalances(2024)['balances']['debit']);
    }

    /**
     * Test the Account Opening Balance in a Currency is that of the given year.
     *
     * @return void
     */
    public function testOpeningBalanceInCurrency()
    {
        $account = $this->account();

        $this->openingBalance($account, $this->period(2023), '2023-01-01', Balance::DEBIT, 500);
        $this->openingBalance($account, $this->period(2024), '2024-01-01', Balance::DEBIT, 2000);

        $this->assertEquals(2000, $account->openingBalance(2024, $account->currency_id)[$account->currency_id]);
    }

    /**
     * Test the Account Schedule in a Currency has the Opening Balances of the
     * Reporting Period only.
     *
     * @return void
     */
    public function testAccountScheduleInCurrency()
    {
        $account = $this->account();
        $prior = $this->period(2023);
        $period = $this->period(2024);

        $this->openingBalance($account, $prior, '2023-01-01', Balance::DEBIT, 500);
        $this->openingBalance($account, $period, '2024-01-01', Balance::DEBIT, 2000);

        $schedule = new AccountSchedule($account->id, $account->currency_id, '2024-10-31');
        $schedule->getTransactions();

        $this->assertCount(1, $schedule->transactions);
        $this->assertEquals(2000, $schedule->balances['originalAmount']);
    }

    /**
     * Test the Trial Balance of a year that does not start in January.
     *
     * @return void
     */
    public function testTrialBalance()
    {
        $this->startYearInJuly();

        $this->period(2023);
        $period = $this->period(2024);

        $trialBalance = new TrialBalance('2024');

        $this->assertEquals($period->id, $trialBalance->reportingPeriod->id);
        $this->assertEquals('2025-06-30', $trialBalance->endDate->toDateString());
    }

    /**
     * Test the section balances count a transaction dated exactly at the start
     * date once, in the movement and not in the opening balance as well.
     *
     * @return void
     */
    public function testSectionBalancesStartDate()
    {
        $this->period(2024);
        $account = $this->account();

        $this->postJournal($account, '2024-10-01', 50, false);

        $balances = Account::sectionBalances([Account::RECEIVABLE], '2024-10-01', '2024-12-31', true, Auth::user()->entity);

        $this->assertEquals(0, $balances['sectionOpeningBalance']);
        $this->assertEquals(50, $balances['sectionClosingBalance']);
    }

    /**
     * Test the closing balance includes a transaction made during the end date.
     *
     * @return void
     */
    public function testClosingBalanceEndDate()
    {
        $this->period(2024);
        $account = $this->account();

        $this->postJournal($account, '2024-12-31 14:30:00', 50, false);

        $reportingCurrencyId = $account->entity->currency_id;

        $this->assertEquals(0, $account->closingBalance('2024-12-30')[$reportingCurrencyId]);
        $this->assertEquals(50, $account->closingBalance('2024-12-31')[$reportingCurrencyId]);
    }

    /**
     * Test the section balances include a transaction made during the end date.
     *
     * @return void
     */
    public function testSectionBalancesEndDate()
    {
        $this->period(2024);
        $account = $this->account();

        $this->postJournal($account, '2024-12-31 14:30:00', 50, false);

        $balances = Account::sectionBalances([Account::RECEIVABLE], '2024-10-01', '2024-12-31', true, Auth::user()->entity);

        $this->assertEquals(50, $balances['sectionClosingBalance']);
    }

    /**
     * Test the Balance Sheet of a year that does not start in January, with
     * activity on both sides of the turn of the calendar year.
     *
     * @return void
     */
    public function testBalanceSheet()
    {
        $this->startYearInJuly();

        $this->period(2023);
        $period = $this->period(2024);
        $account = $this->account();

        $this->openingBalance($account, $period, '2024-07-01', Balance::DEBIT, 2000);
        $this->postJournal($account, '2024-10-05', 500, false);
        $this->postJournal($account, '2025-02-05', 100, false);

        $balanceSheet = new BalanceSheet('2025-03-31');
        $balanceSheet->getSections();

        $this->assertEquals($period->id, $balanceSheet->reportingPeriod->id);
        $this->assertEquals('2024-07-01', $balanceSheet->period['startDate']->toDateString());
        $this->assertEquals(2600, $balanceSheet->totals[BalanceSheet::ASSETS]);
        $this->assertEquals(-600, $balanceSheet->totals[BalanceSheet::LIABILITIES]);
    }

    /**
     * Post the cash flows shared by the Cash Flow Statement tests: 1000 of opening
     * cash and two 300 sales, the second in the calendar year after the first.
     *
     * @param ReportingPeriod $period
     * @param string $openingDate
     * @param string $firstSale
     * @param string $secondSale
     *
     * @return void
     */
    private function postCashFlows(ReportingPeriod $period, string $openingDate, string $firstSale, string $secondSale): void
    {
        $bank = $this->account(Account::BANK);

        $this->openingBalance($bank, $period, $openingDate, Balance::DEBIT, 1000);
        $this->postJournal($bank, $firstSale, 300, false, Account::OPERATING_REVENUE);
        $this->postJournal($bank, $secondSale, 300, false, Account::OPERATING_REVENUE);
    }

    /**
     * Assert the Cash Flow Statement of the cash flows above.
     *
     * @param CashFlowStatement $statement
     *
     * @return void
     */
    private function assertCashFlows(CashFlowStatement $statement): void
    {
        $sections = $statement->getSections();

        $this->assertEquals(600, $sections['balances'][CashFlowStatement::PROFIT]);
        $this->assertEquals(600, $sections['balances'][CashFlowStatement::NET_CASH_FLOW]);
        $this->assertEquals(1000, $sections['balances'][CashFlowStatement::START_CASH_BALANCE]);
        $this->assertEquals(1600, $sections['results'][CashFlowStatement::END_CASH_BALANCE]);
        $this->assertEquals(1600, $sections['results'][CashFlowStatement::CASHBOOK_BALANCE]);
    }

    /**
     * Test the Cash Flow Statement of a past year that starts in January.
     *
     * @return void
     */
    public function testCashFlowStatementPastYear()
    {
        $this->postCashFlows($this->period(2024), '2024-01-01', '2024-03-05', '2024-10-05');

        $this->assertCashFlows(new CashFlowStatement('2024-01-01', '2024-12-31'));
    }

    /**
     * Test the Cash Flow Statement of a past year that does not start in January.
     *
     * @return void
     */
    public function testCashFlowStatementFinancialYear()
    {
        $this->startYearInJuly();

        $this->period(2023);
        $this->postCashFlows($this->period(2024), '2024-07-01', '2024-10-05', '2025-02-05');

        $this->assertCashFlows(new CashFlowStatement('2024-07-01', '2025-06-30'));
    }

    /**
     * Test the Cash Flow Statement of part of a year starts from the cash held
     * before it, reports only its own profit, and stops at its end date.
     *
     * @return void
     */
    public function testCashFlowStatementPartOfYear()
    {
        $this->postCashFlows($this->period(2024), '2024-01-01', '2024-03-05', '2024-10-01');

        $sections = (new CashFlowStatement('2024-10-01', '2024-10-31'))->getSections();

        $this->assertEquals(300, $sections['balances'][CashFlowStatement::PROFIT]);
        $this->assertEquals(1300, $sections['balances'][CashFlowStatement::START_CASH_BALANCE]);
        $this->assertEquals(1600, $sections['results'][CashFlowStatement::END_CASH_BALANCE]);
        $this->assertEquals(1600, $sections['results'][CashFlowStatement::CASHBOOK_BALANCE]);

        $sections = (new CashFlowStatement('2024-04-01', '2024-09-30'))->getSections();

        $this->assertEquals(0, $sections['balances'][CashFlowStatement::PROFIT]);
        $this->assertEquals(1300, $sections['results'][CashFlowStatement::END_CASH_BALANCE]);
    }

    /**
     * Test the current Reporting Period follows the Entity's financial year.
     *
     * @return void
     */
    public function testCurrentReportingPeriod()
    {
        $entity = Auth::user()->entity;
        $entity->year_start = date('n') % 12 + 1;
        $entity->save();

        $year = date('n') < $entity->year_start ? date('Y') - 1 : date('Y');

        $this->assertEquals($year, $entity->current_reporting_period->calendar_year);
    }

    /**
     * Test the Currencies and closing Transactions of a year that does not start
     * in January are those dated inside it, whatever the calendar year.
     *
     * @return void
     */
    public function testForexTranslation()
    {
        $this->startYearInJuly();

        $period = $this->period(2024);
        $currency = factory(Currency::class)->create();

        ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'currency_id' => $currency->id,
            ])->id,
            'reporting_period_id' => $period->id,
        ]);

        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency->id,
        ]);

        $transaction = new ClientReceipt([
            'account_id' => $account->id,
            'transaction_date' => '2025-03-15',
            'narration' => $this->faker->word,
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'rate' => 110,
                'currency_id' => $currency->id,
            ])->id,
            'currency_id' => $currency->id,
        ]);
        $transaction->addLineItem(new LineItem([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            'amount' => 100,
        ]));
        $transaction->post();

        $this->assertEquals([$currency->id], $period->transactionCurrencies()->pluck('currency_id')->all());

        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null,
        ]);

        $period->status = ReportingPeriod::ADJUSTING;
        $translations = $period->prepareBalancesTranslation($forex->id);

        $this->assertCount(1, $translations);
        $this->assertEquals('2025-06-30', Carbon::parse($translations[0]->transaction_date)->toDateString());
        $this->assertTrue($account->isClosed(2024));
        $this->assertFalse($account->isClosed(2025));
    }
}
