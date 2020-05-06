<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;
use IFRS\Models\User;
use IFRS\Models\Transaction;

use IFRS\Exceptions\InvalidBalanceTransaction;
use IFRS\Exceptions\InvalidAccountClassBalance;
use IFRS\Exceptions\InvalidBalance;
use IFRS\Exceptions\NegativeAmount;

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

        $account = factory(Account::class)->create(
            [
            'account_type' => Account::INVENTORY,
            ]
        );

        $exchangeRate = factory(ExchangeRate::class)->create();

        $balance = new Balance(
            [
            'exchange_rate_id' => $exchangeRate->id,
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'year' => Carbon::now()->year,
            'transaction_no' => $this->faker->word,
            'transaction_type' => Transaction::JN,
            'reference' => $this->faker->word,
            'balance_type' =>  Balance::DEBIT,
            'amount' => $this->faker->randomFloat(2),
            ]
        );
        $balance->save();

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

        factory(Balance::class)->create(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OPERATING_REVENUE
                ]
            )->id,
            ]
        );
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

        factory(Balance::class)->create(
            [
            'transaction_type' => Transaction::CN,
            ]
        );
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

        factory(Balance::class)->create(
            [
            "balance_type" => "X"
            ]
        );
    }

    /**
     * Test Balance Negative Amount.
     *
     * @return void
     */
    public function testBalanceNegativeAmount()
    {
        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('Balance Amount cannot be negative');

        factory(Balance::class)->create(
            [
            "amount" => -100
            ]
        );
    }
}
