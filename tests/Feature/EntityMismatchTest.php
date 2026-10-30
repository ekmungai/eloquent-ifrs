<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;

use PHPUnit\Framework\Attributes\DataProvider;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\ReportingPeriod;

use IFRS\Reports\AgingSchedule;
use IFRS\Reports\BalanceSheet;
use IFRS\Reports\CashFlowStatement;
use IFRS\Reports\IncomeStatement;
use IFRS\Reports\TrialBalance;

use IFRS\Exceptions\EntityMismatch;

class EntityMismatchTest extends TestCase
{
    /**
     * Create another Entity with a Reporting Period, leaving the current User logged in.
     *
     * @return Entity
     */
    private function otherEntity(): Entity
    {
        $user = Auth::user();
        $other = factory(User::class)->create();

        $this->be($other);

        $entity = $other->entity;
        $entity->currency_id = factory(Currency::class)->create()->id;
        $entity->save();

        factory(ReportingPeriod::class)->create([
            'calendar_year' => date('Y'),
            'entity_id' => $entity->id,
        ]);

        $this->be($user);

        return $entity->fresh();
    }

    /**
     * Calls that take an Entity.
     *
     * @return array
     */
    public static function entityCalls(): array
    {
        return [
            'balance sheet' => [fn ($entity) => new BalanceSheet(null, $entity)],
            'income statement' => [fn ($entity) => new IncomeStatement(null, null, $entity)],
            'cash flow statement' => [fn ($entity) => new CashFlowStatement(null, null, $entity)],
            'trial balance' => [fn ($entity) => new TrialBalance(null, $entity)],
            'aging schedule' => [fn ($entity) => new AgingSchedule(Account::RECEIVABLE, null, null, $entity)],
            'section balances' => [fn ($entity) => Account::sectionBalances([Account::BANK], null, null, true, $entity)],
            'opening balances' => [fn ($entity) => Account::openingBalances((int) date('Y'), $entity)],
            'monthly results' => [fn ($entity) => IncomeStatement::getResults(date('m'), date('Y'), $entity)],
            'reporting period' => [fn ($entity) => ReportingPeriod::getPeriod(null, $entity)],
        ];
    }

    /**
     * Test asking for another Entity fails rather than coming back empty.
     *
     * @return void
     */
    #[DataProvider('entityCalls')]
    public function testOtherEntity(callable $call)
    {
        $entity = $this->otherEntity();

        $this->expectException(EntityMismatch::class);

        $call($entity);
    }

    /**
     * Test asking for the logged in User's own Entity still works.
     *
     * @return void
     */
    #[DataProvider('entityCalls')]
    public function testOwnEntity(callable $call)
    {
        $this->assertNotNull($call(Auth::user()->entity) ?? true);
    }

    /**
     * Test any Entity can be asked for with nobody logged in, as on the console.
     *
     * @return void
     */
    #[DataProvider('entityCalls')]
    public function testNobodyLoggedIn(callable $call)
    {
        $entity = $this->otherEntity();

        Auth::logout();

        $this->assertNotNull($call($entity) ?? true);
    }
}
