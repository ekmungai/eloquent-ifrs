<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Balance;
use IFRS\Models\Category;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;

use IFRS\Reports\AccountSchedule;
use IFRS\Reports\AccountStatement;
use IFRS\Reports\AgingSchedule;
use IFRS\Reports\BalanceSheet;
use IFRS\Reports\IncomeStatement;

use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\ClientReceipt;
use IFRS\Transactions\JournalEntry;

class ReportAccuracyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        factory(ReportingPeriod::class)->create([
            'calendar_year' => 2024,
            'entity_id' => Auth::user()->entity->id,
        ]);
    }

    /**
     * Create an Account in the reporting currency.
     *
     * @param string $type
     * @param int $categoryId
     *
     * @return Account
     */
    private function account(string $type, ?int $categoryId = null): Account
    {
        return factory(Account::class)->create([
            'account_type' => $type,
            'category_id' => $categoryId,
            'currency_id' => Auth::user()->entity->currency_id,
        ]);
    }

    /**
     * Post a Journal Entry against the Account.
     *
     * @param Account $account
     * @param string $date
     * @param float $amount
     * @param string $lineAccountType
     * @param bool $credited
     *
     * @return void
     */
    private function postJournal(Account $account, string $date, float $amount, string $lineAccountType, bool $credited = false): void
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
            'account_id' => $this->account($lineAccountType)->id,
        ]));
        $journalEntry->post();
    }

    /**
     * Post a Client Invoice of 100 in March 2024.
     *
     * @param Account $client
     *
     * @return ClientInvoice
     */
    private function postInvoice(Account $client): ClientInvoice
    {
        $invoice = new ClientInvoice([
            'account_id' => $client->id,
            'transaction_date' => '2024-03-10',
            'narration' => $this->faker->word,
            'currency_id' => $client->currency_id,
        ]);
        $invoice->addLineItem(factory(LineItem::class)->create([
            'amount' => 100,
            'quantity' => 1,
            'account_id' => $this->account(Account::OPERATING_REVENUE)->id,
        ]));
        $invoice->post();

        return $invoice;
    }

    /**
     * Clear the Client Invoice in full on 1 August 2024.
     *
     * @param Account $client
     * @param ClientInvoice $invoice
     *
     * @return void
     */
    private function clearInvoice(Account $client, ClientInvoice $invoice): void
    {
        $receipt = new ClientReceipt([
            'account_id' => $client->id,
            'transaction_date' => '2024-08-01',
            'narration' => $this->faker->word,
            'currency_id' => $client->currency_id,
        ]);
        $receipt->addLineItem(factory(LineItem::class)->create([
            'amount' => 100,
            'quantity' => 1,
            'account_id' => $this->account(Account::BANK)->id,
        ]));
        $receipt->post();

        Assignment::create([
            'assignment_date' => '2024-08-01',
            'transaction_id' => $receipt->id,
            'cleared_id' => $invoice->id,
            'cleared_type' => $invoice->cleared_type,
            'amount' => 100,
        ]);
    }

    /**
     * Test reports given only an end date cover the year of that date.
     *
     * @return void
     */
    public function testEndDateWithoutStartDate()
    {
        $client = $this->account(Account::RECEIVABLE);
        $this->postInvoice($client);

        $schedule = new AccountSchedule($client->id, null, '2024-10-31');
        $schedule->getTransactions();

        $statement = new AccountStatement($client->id, null, null, '2024-10-31');
        $statement->getTransactions();

        $incomeStatement = new IncomeStatement(null, '2024-12-31');
        $incomeStatement->getSections();

        $aging = new AgingSchedule(Account::RECEIVABLE, '2024-10-31');

        $this->assertCount(1, $schedule->transactions);
        $this->assertCount(1, $statement->transactions);
        $this->assertEquals(100, $incomeStatement->results[IncomeStatement::NET_PROFIT]);
        $this->assertEquals(100, array_sum($aging->balances));
    }

    /**
     * Test schedules count only what had been cleared by their end date.
     *
     * @return void
     */
    public function testSchedulesAsAtEndDate()
    {
        $client = $this->account(Account::RECEIVABLE);
        $this->clearInvoice($client, $this->postInvoice($client));

        $before = new AccountSchedule($client->id, null, '2024-07-31');
        $before->getTransactions();

        $this->assertEquals(100, $before->balances['unclearedAmount']);
        $this->assertEquals(0, $before->balances['amountCleared']);

        $onTheDay = new AccountSchedule($client->id, null, '2024-08-01');
        $onTheDay->getTransactions();

        $this->assertEquals(0, $onTheDay->balances['unclearedAmount']);

        $aging = new AgingSchedule(Account::RECEIVABLE, '2024-07-31');

        $this->assertEquals(100, array_sum($aging->balances));
    }

    /**
     * Post a period with a 180 loss: revenue 100, other revenue 20, expenses 300.
     *
     * @return void
     */
    private function postLoss(): void
    {
        $bank = $this->account(Account::BANK);

        $this->postJournal($bank, '2024-03-05', 100, Account::OPERATING_REVENUE);
        $this->postJournal($bank, '2024-03-06', 20, Account::NON_OPERATING_REVENUE);
        $this->postJournal($bank, '2024-03-07', 300, Account::OPERATING_EXPENSE, true);
    }

    /**
     * Test the monthly results keep the sign of a loss.
     *
     * @return void
     */
    public function testMonthlyResultsOfLoss()
    {
        $this->postLoss();

        $results = IncomeStatement::getResults(3, 2024);

        $this->assertEquals(100, $results[IncomeStatement::OPERATING_REVENUES]);
        $this->assertEquals(20, $results[IncomeStatement::NON_OPERATING_REVENUES]);
        $this->assertEquals(300, $results[IncomeStatement::OPERATING_EXPENSES]);
        $this->assertEquals(-200, $results[IncomeStatement::GROSS_PROFIT]);
        $this->assertEquals(-180, $results[IncomeStatement::NET_PROFIT]);
    }

    /**
     * Test the monthly results agree with the Income Statement.
     *
     * @return void
     */
    public function testMonthlyResultsAgreeWithIncomeStatement()
    {
        $this->postLoss();

        $results = IncomeStatement::getResults(3, 2024);

        $incomeStatement = new IncomeStatement('2024-03-01', '2024-03-31');
        $incomeStatement->getSections();

        $this->assertEquals($incomeStatement->results[IncomeStatement::GROSS_PROFIT], $results[IncomeStatement::GROSS_PROFIT]);
        $this->assertEquals($incomeStatement->results[IncomeStatement::NET_PROFIT], $results[IncomeStatement::NET_PROFIT]);
    }

    /**
     * Test the Balance Sheet keeps the sign of negative equity.
     *
     * @return void
     */
    public function testNegativeEquity()
    {
        $this->postJournal($this->account(Account::EQUITY), '2024-02-01', 100, Account::BANK, true);
        $this->postJournal($this->account(Account::BANK), '2024-03-07', 500, Account::OPERATING_EXPENSE, true);

        $balanceSheet = new BalanceSheet('2024-12-31');
        $balanceSheet->getSections();

        $this->assertEquals(-400, $balanceSheet->results[BalanceSheet::TOTAL_EQUITY]);
    }

    /**
     * Test the Balance Sheet reports positive equity as positive.
     *
     * @return void
     */
    public function testPositiveEquity()
    {
        $this->postJournal($this->account(Account::EQUITY), '2024-02-01', 900, Account::BANK, true);
        $this->postJournal($this->account(Account::BANK), '2024-03-07', 500, Account::OPERATING_EXPENSE, true);

        $balanceSheet = new BalanceSheet('2024-12-31');
        $balanceSheet->getSections();

        $this->assertEquals(400, $balanceSheet->results[BalanceSheet::TOTAL_EQUITY]);
    }

    /**
     * Test a Category's balances include opening balances by default.
     *
     * @return void
     */
    public function testCategoryOpeningBalances()
    {
        $category = factory(Category::class)->create(['category_type' => Account::RECEIVABLE]);
        $account = $this->account(Account::RECEIVABLE, $category->id);

        factory(Balance::class)->create([
            'account_id' => $account->id,
            'balance_type' => Balance::DEBIT,
            'exchange_rate_id' => factory(ExchangeRate::class)->create(['rate' => 1])->id,
            'reporting_period_id' => $this->period->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => date('Y') . '-01-01',
            'balance' => 500,
        ]);

        $this->assertEquals(500, $category->getAccountBalances()['total']);
    }
}
