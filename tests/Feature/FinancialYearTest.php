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
     * @return Account
     */
    private function account(): Account
    {
        return factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
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
     *
     * @return void
     */
    private function postJournal(Account $account, string $date, float $amount, bool $credited): void
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
                'account_type' => Account::CONTROL,
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
