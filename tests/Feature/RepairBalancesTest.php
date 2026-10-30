<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Entity;
use IFRS\Models\ExchangeRate;
use IFRS\Models\ReportingPeriod;

use IFRS\Reports\AccountStatement;

use IFRS\Support\MisattachedBalances;

class RepairBalancesTest extends TestCase
{
    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var ReportingPeriod
     */
    private $year2024;

    /**
     * @var ReportingPeriod
     */
    private $year2025;

    public function setUp(): void
    {
        parent::setUp();

        $this->entity = Auth::user()->entity;
        $this->entity->year_start = 7;
        $this->entity->save();

        foreach ([2024, 2025] as $year) {
            $this->{"year$year"} = factory(ReportingPeriod::class)->create([
                'calendar_year' => $year,
                'entity_id' => $this->entity->id,
            ]);
        }
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
     * Write a Balance as it would have been written at the given time.
     *
     * @param Account $account
     * @param ReportingPeriod $period
     * @param string $createdAt
     * @param array $attributes
     *
     * @return Balance
     */
    private function balanceWrittenAt(Account $account, ReportingPeriod $period, string $createdAt, array $attributes = []): Balance
    {
        $balance = factory(Balance::class)->create(array_merge([
            'account_id' => $account->id,
            'balance_type' => Balance::DEBIT,
            'exchange_rate_id' => factory(ExchangeRate::class)->create(['rate' => 1])->id,
            'reporting_period_id' => $period->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2024-07-01',
            'balance' => 2000,
        ], $attributes));

        // created_at is the only record of when the default was applied
        DB::table($balance->getTable())->where('id', $balance->id)->update(['created_at' => $createdAt]);

        return $balance->fresh();
    }

    /**
     * Assert the opening balances of the Account in the two years.
     *
     * @param Account $account
     * @param float $year2024
     * @param float $year2025
     *
     * @return void
     */
    private function assertOpeningBalances(Account $account, float $year2024, float $year2025): void
    {
        $account = Account::find($account->id);

        $this->assertEquals($year2024, $account->openingBalance(2024)[$this->entity->currency_id]);
        $this->assertEquals($year2025, $account->openingBalance(2025)[$this->entity->currency_id]);
    }

    /**
     * Test the damage: the opening balance of the year is missing from its reports
     * and turns up in those of the year after, until the repair is applied.
     *
     * @return void
     */
    public function testRepairRestoresReports()
    {
        $account = $this->account();

        // Saved in March 2025, in the 2024 financial year, with no explicit period
        $this->balanceWrittenAt($account, $this->year2025, '2025-03-10 09:00:00');

        $this->assertOpeningBalances($account, 0, 2000);

        $statement = new AccountStatement($account->id, null, '2024-10-01', '2024-10-31');
        $statement->getTransactions();

        $this->assertEquals(0, $statement->balances['opening']);

        $this->artisan('ifrs:repair-balances', ['--apply' => true])->assertExitCode(0);

        $this->assertOpeningBalances($account, 2000, 0);

        $statement = new AccountStatement($account->id, null, '2024-10-01', '2024-10-31');
        $statement->getTransactions();

        $this->assertEquals(2000, $statement->balances['opening']);
        $this->assertCount(0, MisattachedBalances::find());
    }

    /**
     * Test the command changes nothing unless told to.
     *
     * @return void
     */
    public function testDryRun()
    {
        $account = $this->account();

        $this->balanceWrittenAt($account, $this->year2025, '2025-03-10 09:00:00');

        $this->artisan('ifrs:repair-balances')
            ->expectsOutputToContain('Nothing was changed')
            ->assertExitCode(0);

        $this->assertOpeningBalances($account, 0, 2000);
    }

    /**
     * Test Balances without the fingerprint of the fault are left alone.
     *
     * @return void
     */
    public function testCorrectBalancesAreNotFound()
    {
        // Saved in March 2025, in the period that was then current
        $this->balanceWrittenAt($this->account(), $this->year2024, '2025-03-10 09:00:00');

        // Saved in August 2025, after the financial year began
        $this->balanceWrittenAt($this->account(), $this->year2025, '2025-08-10 09:00:00');

        // Saved in March 2024 and given a later year on purpose
        $this->balanceWrittenAt($this->account(), $this->year2025, '2024-03-10 09:00:00');

        $this->assertCount(0, MisattachedBalances::find());

        $this->artisan('ifrs:repair-balances')
            ->expectsOutputToContain('No misattached Balances found')
            ->assertExitCode(0);
    }

    /**
     * Test a year starting in January cannot have been affected.
     *
     * @return void
     */
    public function testJanuaryYearIsNotChecked()
    {
        $this->balanceWrittenAt($this->account(), $this->year2025, '2025-03-10 09:00:00');

        $this->entity->year_start = 1;
        $this->entity->save();

        $this->assertCount(0, MisattachedBalances::find());
    }

    /**
     * Test the default transaction number follows the Balance to its period, and
     * one that was set by hand does not change.
     *
     * @return void
     */
    public function testTransactionNumbers()
    {
        $account = $this->account();
        $default = $account->id . $account->currency->currency_code;

        $generated = $this->balanceWrittenAt($account, $this->year2025, '2025-03-10 09:00:00', [
            'transaction_no' => $default . '2025',
        ]);
        $manual = $this->balanceWrittenAt($account, $this->year2025, '2025-03-10 09:00:00', [
            'transaction_no' => 'OB-17',
        ]);

        MisattachedBalances::repair(MisattachedBalances::find());

        $this->assertEquals($default . '2024', $generated->fresh()->transaction_no);
        $this->assertEquals('OB-17', $manual->fresh()->transaction_no);
    }

    /**
     * Test a Balance is not moved into or out of a closed Reporting Period.
     *
     * @return void
     */
    public function testClosedPeriodIsBlocked()
    {
        $account = $this->account();

        $this->balanceWrittenAt($account, $this->year2025, '2025-03-10 09:00:00');

        $this->year2024->status = ReportingPeriod::CLOSED;
        $this->year2024->save();

        $findings = MisattachedBalances::find();

        $this->assertEquals(MisattachedBalances::CLOSED_PERIOD, $findings->first()->blocked);
        $this->assertEquals(0, MisattachedBalances::repair($findings));
        $this->assertOpeningBalances($account, 0, 2000);
    }

    /**
     * Test a Balance is not moved when the year it belongs to has no Reporting Period.
     *
     * @return void
     */
    public function testMissingPeriodIsBlocked()
    {
        $period = factory(ReportingPeriod::class)->create([
            'calendar_year' => 2022,
            'entity_id' => $this->entity->id,
        ]);

        $this->balanceWrittenAt($this->account(), $period, '2022-03-10 09:00:00');

        $findings = MisattachedBalances::find();

        $this->assertCount(1, $findings);
        $this->assertEquals(MisattachedBalances::MISSING_PERIOD, $findings->first()->blocked);
    }

    /**
     * Test the command can be limited to an Entity.
     *
     * @return void
     */
    public function testEntityOption()
    {
        $this->balanceWrittenAt($this->account(), $this->year2025, '2025-03-10 09:00:00');

        $other = factory(Entity::class)->create(['year_start' => 7]);

        $this->artisan('ifrs:repair-balances', ['--entity' => $other->id])
            ->expectsOutputToContain('No misattached Balances found')
            ->assertExitCode(0);

        $this->artisan('ifrs:repair-balances', ['--entity' => 999999])->assertExitCode(1);
    }

    /**
     * Test the command works from the console, where nobody is logged in.
     *
     * @return void
     */
    public function testWithoutLoggedInUser()
    {
        $account = $this->account();
        $user = Auth::user();

        $this->balanceWrittenAt($account, $this->year2025, '2025-03-10 09:00:00');

        Auth::logout();

        $this->artisan('ifrs:repair-balances', ['--apply' => true])->assertExitCode(0);

        $this->be($user);

        $this->assertOpeningBalances($account, 2000, 0);
    }

    /**
     * Test a Balance saved now gets the period of the financial year it is saved in,
     * so that the repair has no new data to find.
     *
     * @return void
     */
    public function testNewBalancesAreNotMisattached()
    {
        // The next month, so that today falls in the months the fault affected
        $this->entity->year_start = date('n') % 12 + 1;
        $this->entity->save();

        $year = date('n') < $this->entity->year_start ? date('Y') - 1 : date('Y');

        $account = $this->account();

        $balance = Balance::create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => ReportingPeriod::periodStart(null, $this->entity),
            'balance' => 2000,
        ]);

        $this->assertEquals($year, ReportingPeriod::find($balance->reporting_period_id)->calendar_year);
    }
}
