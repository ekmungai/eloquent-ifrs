<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\RecycledObject;
use Ekmungai\IFRS\Models\User;
use Ekmungai\IFRS\Models\Transaction;

use Ekmungai\IFRS\Exceptions\InvalidBalanceTransaction;
use Ekmungai\IFRS\Exceptions\InvalidAccountClassBalance;
use Ekmungai\IFRS\Exceptions\InvalidBalance;
use Ekmungai\IFRS\Exceptions\NegativeAmount;

class AccountBalanceTest extends TestCase
{
    /**
     * Balance Model relationships test.
     *
     * @return void
     */
    public function testBalanceRelationships()
    {
        $currency = factory(Currency::class)->create();

        $account = factory(Account::class)->create([
            'account_type' => Account::INVENTORY,
        ]);

        $exchangeRate = factory(ExchangeRate::class)->create();

        $balance = Balance::new(
            $account,
            Carbon::now()->year,
            $this->faker->word,
            $this->faker->randomFloat(2),
            Balance::D,
            Transaction::JN,
            $currency,
            $exchangeRate
        );

        $this->assertEquals($balance->currency->name, $currency->name);
        $this->assertEquals($balance->account->name, $account->name);
        $this->assertEquals($balance->exchangeRate->rate, $exchangeRate->rate);
    }

    /**
     * Test Balance model Entity Scope.
     *
     * @return void
     */
    public function testBalanceEntityScope()
    {
        factory(Balance::class, 3)->create()->each(
            function ($balance) {
                $balance->entity_id = 2;
                $balance->save();
            }
        );

        $this->assertEquals(count(Balance::all()), 0);

        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $this->assertEquals(count(Balance::all()), 3);
    }

    /**
     * Test Balance Model recylcling
     *
     * @return void
     */
    public function testBalanceRecycling()
    {
        $balance = factory(Balance::class)->create();
        $balance->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($balance->recycled->first(), $recycled);
    }

    /**
     * Test Wrong Account Class Balance.
     *
     * @return void
     */
    public function testInvalidAccountClassBalance()
    {
        $this->expectException(InvalidAccountClassBalance::class);
        $this->expectExceptionMessage('Income Statement Accounts cannot have Opening Balances');

        factory(Balance::class)->create([
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::OPERATING_REVENUE
            ])->id,
        ]);
    }

    /**
     * Test Wrong Balance Transaction.
     *
     * @return void
     */
    public function testInvalidBalanceTransaction()
    {
        $this->expectException(InvalidBalanceTransaction::class);
        $this->expectExceptionMessage(
            'Opening Balance Transaction must be one of: Client Invoice, Supplier Bill, Journal Entry'
        );

        $balance = Balance::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECEIVABLE
            ]),
            Carbon::now()->year,
            $this->faker->word,
            $this->faker->randomFloat(2),
            Balance::D,
            Transaction::CN
        );
        $balance->save();
    }

    /**
     * Test Wrong Balance Type.
     *
     * @return void
     */
    public function testInvalidBalance()
    {
        $this->expectException(InvalidBalance::class);
        $this->expectExceptionMessage('Opening Balance Type must be one of: Debit, Credit');

        $balance = Balance::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECEIVABLE
            ]),
            Carbon::now()->year,
            $this->faker->word,
            $this->faker->randomFloat(2),
            'X',
            Transaction::JN
        );
        $balance->save();
    }

    /**
     * Test Negative Amount.
     *
     * @return void
     */
    public function testNegativeAmount()
    {
        $balance = Balance::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECEIVABLE
            ]),
            Carbon::now()->year,
            $this->faker->word,
            -100,
            Balance::D,
            Transaction::JN
        );
        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('Balance Amount cannot be negative');

        $balance->save();
    }
}
