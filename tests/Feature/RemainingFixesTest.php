<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Balance;
use IFRS\Models\Category;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Transaction;

use IFRS\Reports\AccountSchedule;
use IFRS\Reports\AccountStatement;
use IFRS\Reports\AgingSchedule;
use IFRS\Reports\BalanceSheet;
use IFRS\Reports\CashFlowStatement;
use IFRS\Reports\IncomeStatement;
use IFRS\Reports\TrialBalance;

use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\ClientReceipt;

use IFRS\Exceptions\ClosedReportingPeriod;
use IFRS\Exceptions\InvalidBalanceDate;

class RemainingFixesTest extends TestCase
{
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
     * Create an opening Balance.
     *
     * @param Account $account
     * @param ReportingPeriod $period
     * @param string $date
     *
     * @return Balance
     */
    private function balance(Account $account, ReportingPeriod $period, string $date): Balance
    {
        return factory(Balance::class)->create([
            'account_id' => $account->id,
            'balance_type' => Balance::DEBIT,
            'exchange_rate_id' => factory(ExchangeRate::class)->create(['rate' => 1])->id,
            'reporting_period_id' => $period->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => $date,
            'balance' => 100,
        ]);
    }

    /**
     * Post a Client Invoice.
     *
     * @param Account $client
     * @param mixed $date
     * @param float $amount
     *
     * @return ClientInvoice
     */
    private function invoice(Account $client, $date, float $amount): ClientInvoice
    {
        $invoice = new ClientInvoice([
            'account_id' => $client->id,
            'transaction_date' => $date,
            'narration' => $this->faker->word,
            'currency_id' => $client->currency_id,
        ]);
        $invoice->addLineItem(factory(LineItem::class)->create([
            'amount' => $amount,
            'quantity' => 1,
            'account_id' => $this->account(Account::OPERATING_REVENUE)->id,
        ]));
        $invoice->post();

        return $invoice;
    }

    /**
     * Post a Client Receipt.
     *
     * @param Account $client
     * @param mixed $date
     * @param float $amount
     *
     * @return ClientReceipt
     */
    private function receipt(Account $client, $date, float $amount): ClientReceipt
    {
        $receipt = new ClientReceipt([
            'account_id' => $client->id,
            'transaction_date' => $date,
            'narration' => $this->faker->word,
            'currency_id' => $client->currency_id,
        ]);
        $receipt->addLineItem(factory(LineItem::class)->create([
            'amount' => $amount,
            'quantity' => 1,
            'account_id' => $this->account(Account::BANK)->id,
        ]));
        $receipt->post();

        return $receipt;
    }

    /**
     * Test a Transaction subclass sees its clearances.
     *
     * @return void
     */
    public function testClearancesOfSubclass()
    {
        $client = $this->account(Account::RECEIVABLE);
        $invoice = $this->invoice($client, Carbon::now()->subMinutes(5), 100);
        $receipt = $this->receipt($client, Carbon::now(), 100);

        Assignment::create([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $receipt->id,
            'cleared_id' => $invoice->id,
            'cleared_type' => $invoice->cleared_type,
            'amount' => 40,
        ]);

        $this->assertEquals(40, ClientInvoice::find($invoice->id)->cleared_amount);
        $this->assertEquals(40, Transaction::find($invoice->id)->cleared_amount);
    }

    /**
     * Test the clearances of a Balance are not those of a Transaction with its id.
     *
     * @return void
     */
    public function testClearancesAreKeptApartByType()
    {
        $client = $this->account(Account::RECEIVABLE);
        $invoice = $this->invoice($client, Carbon::now()->subMinutes(5), 100);
        $receipt = $this->receipt($client, Carbon::now(), 100);

        $balance = $this->balance($client, $this->period->fresh(), date('Y') . '-01-01');

        Assignment::create([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $receipt->id,
            'cleared_id' => $balance->id,
            'cleared_type' => $balance->cleared_type,
            'amount' => 40,
        ]);

        $this->assertEquals(40, $balance->fresh()->cleared_amount);

        $this->assertEquals(0, Transaction::find($invoice->id)->cleared_amount);
    }

    /**
     * Test the reports raise no deprecation notices.
     *
     * @return void
     */
    public function testReportsDeclareTheirProperties()
    {
        $client = $this->account(Account::RECEIVABLE);
        $notices = [];

        set_error_handler(function ($level, $message) use (&$notices) {
            $notices[] = $message;

            return true;
        }, E_DEPRECATED);

        new AccountStatement($client->id);
        new AccountSchedule($client->id);
        new AgingSchedule();
        new BalanceSheet();
        new IncomeStatement();
        new TrialBalance();
        new CashFlowStatement();

        restore_error_handler();

        $this->assertEquals([], $notices);
    }

    /**
     * Test a Balance cannot be dated after the start of its own Reporting Period.
     *
     * @return void
     */
    public function testBalanceDateAgainstItsOwnPeriod()
    {
        $this->expectException(InvalidBalanceDate::class);

        $this->balance($this->account(Account::RECEIVABLE), $this->period(2024), '2024-09-01');
    }

    /**
     * Test a Balance can be dated up to the start of its Reporting Period, and later
     * with the mid year flag.
     *
     * @return void
     */
    public function testValidBalanceDates()
    {
        $period = $this->period(2024);

        $this->balance($this->account(Account::RECEIVABLE), $period, '2024-01-01');

        $entity = Auth::user()->entity;
        $entity->mid_year_balances = true;
        $entity->save();

        $this->balance($this->account(Account::RECEIVABLE), $period, '2024-09-01');

        $this->assertEquals(2, Balance::where('reporting_period_id', $period->id)->count());
    }

    /**
     * Test a Balance stored before its date was checked against its own period can
     * still be saved and restored, as long as the date is left alone.
     *
     * @return void
     */
    public function testExistingBalanceDateIsNotRechecked()
    {
        $balance = $this->balance($this->account(Account::RECEIVABLE), $this->period(2024), '2024-01-01');

        DB::table($balance->getTable())->where('id', $balance->id)->update(['transaction_date' => '2024-09-01']);

        $balance = $balance->fresh();
        $balance->reference = 'Edited';
        $balance->save();

        $balance->delete();
        $balance->restore();

        $this->assertEquals('Edited', $balance->fresh()->reference);

        $this->expectException(InvalidBalanceDate::class);

        $balance = $balance->fresh();
        $balance->transaction_date = '2024-10-01';
        $balance->save();
    }

    /**
     * Test a Balance cannot be added to a closed Reporting Period.
     *
     * @return void
     */
    public function testBalanceInClosedPeriod()
    {
        $period = $this->period(2024);
        $period->status = ReportingPeriod::CLOSED;
        $period->save();

        $this->expectException(ClosedReportingPeriod::class);

        $this->balance($this->account(Account::RECEIVABLE), $period, '2024-01-01');
    }

    /**
     * Test the amount of a Balance in a closed Reporting Period cannot be changed,
     * while the rest of it can.
     *
     * @return void
     */
    public function testBalanceAlreadyInClosedPeriod()
    {
        $period = $this->period(2024);
        $balance = $this->balance($this->account(Account::RECEIVABLE), $period, '2024-01-01');

        $period->status = ReportingPeriod::CLOSED;
        $period->save();

        $balance = $balance->fresh();
        $balance->reference = 'Edited';
        $balance->save();

        $this->assertEquals('Edited', $balance->fresh()->reference);

        $this->expectException(ClosedReportingPeriod::class);

        $balance->balance = 200;
        $balance->save();
    }

    /**
     * Test bulk assigning stops once the Transaction's balance has run out.
     *
     * @return void
     */
    public function testBulkAssignStopsWhenExhausted()
    {
        $client = $this->account(Account::RECEIVABLE);

        $this->invoice($client, Carbon::now()->subMinutes(5), 100);
        $this->invoice($client, Carbon::now()->subMinutes(4), 50);

        $receipt = $this->receipt($client, Carbon::now(), 100);

        Assignment::bulkAssign($receipt);

        $this->assertEquals([100], Assignment::where('transaction_id', $receipt->id)->pluck('amount')->all());
    }

    /**
     * Test the Transaction numbers of a period ignore later periods.
     *
     * @return void
     */
    public function testTransactionNumberIgnoresLaterPeriods()
    {
        $this->period(2024);
        $this->period(2025);

        $client = $this->account(Account::RECEIVABLE);

        foreach (['2025-02-01', '2025-03-01', '2025-04-01'] as $date) {
            $this->invoice($client, $date, 10);
        }

        $this->assertStringEndsWith('/0001', $this->invoice($client, '2024-05-01', 10)->transaction_no);
    }

    /**
     * Test Categories that share a name are not added together.
     *
     * @return void
     */
    public function testCategoriesWithTheSameName()
    {
        $period = $this->period->fresh();
        $accounts = [];

        foreach ([1, 2] as $index) {
            $category = factory(Category::class)->create([
                'name' => 'Clients',
                'category_type' => Account::RECEIVABLE,
            ]);
            $accounts[$index] = $this->account(Account::RECEIVABLE, $category->id);
            $this->balance($accounts[$index], $period, date('Y') . '-01-01');
        }

        $this->balance($this->account(Account::RECEIVABLE, $accounts[2]->category_id), $period, date('Y') . '-01-01');

        $categories = Account::sectionBalances([Account::RECEIVABLE])['sectionCategories'];

        $this->assertCount(2, $categories);
        $this->assertEquals(100, $categories['Clients']['total']);
        $this->assertEquals(200, $categories['Clients (' . $accounts[2]->category_id . ')']['total']);
    }
}
