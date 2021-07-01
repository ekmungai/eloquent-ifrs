<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;
use IFRS\User;
use IFRS\Models\Transaction;

use IFRS\Exceptions\InvalidBalanceTransaction;
use IFRS\Exceptions\InvalidAccountClassBalance;
use IFRS\Exceptions\NegativeAmount;
use IFRS\Exceptions\InvalidBalanceType;
use IFRS\Exceptions\InvalidBalanceDate;
use IFRS\Exceptions\InvalidCurrency;
use IFRS\Models\Entity;
use Illuminate\Support\Facades\Auth;

class AccountBalanceTest extends TestCase
{
    /**
     * Balance Model relationships test.
     *
     * @return void
     */
    public function testBalanceRelationships()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::INVENTORY,
            'category_id' => null,
        ]);

        $exchangeRate = factory(ExchangeRate::class)->create();
        $currency = factory(Currency::class)->create([
            'currency_code' => 'EUR'
        ]);
        $balance = new Balance([
            'exchange_rate_id' => $exchangeRate->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'transaction_type' => Transaction::JN,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'reference' => $this->faker->word,
            'balance_type' =>  Balance::DEBIT,
            'amount' => $this->faker->randomFloat(2),
        ]);
        $balance->save();

        $this->assertEquals($balance->account->name, $account->name);
        $this->assertEquals($balance->exchangeRate->rate, $exchangeRate->rate);
        $this->assertEquals($balance->reportingPeriod->calendar_year, date("Y"));
        $this->assertEquals($balance->transaction_no, $account->id . 'EUR' . date("Y"));
        $this->assertEquals(
            $balance->toString(true),
            'Debit Balance: ' . $balance->account->toString() . ' for year ' . Carbon::now()->year
        );
        $this->assertEquals(
            $balance->toString(),
            $balance->account->toString() . ' for year ' . Carbon::now()->year
        );
        $this->assertEquals($balance->type, Balance::getType(Balance::DEBIT));
    }

    /**
     * Balance Model Account Currency test.
     *
     * @return void
     */
    public function testBalanceAccountCurrency()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
        ]);

        $exchangeRate = factory(ExchangeRate::class)->create([
            'rate' => 105
        ]);

        $balance = new Balance([
            'exchange_rate_id' => $exchangeRate->id,
            'account_id' => $account->id,
            'transaction_type' => Transaction::JN,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'reference' => $this->faker->word,
            'currency_id' => factory(Currency::class)->create([
                'currency_code' => 'USD'
            ])->id,
            'balance_type' =>  Balance::DEBIT,
            'balance' => 50,
        ]);
        $balance->save();

        $this->assertEquals($balance->transaction_no, $account->id . 'USD' . date("Y"));
        $this->assertEquals($balance->amount, 50);
    }

    /**
     * Test Balance model Entity Scope.
     *
     * @return void
     */
    public function testBalanceEntityScope()
    {
        $newEntity = factory(Entity::class)->create();

        factory(Balance::class, 3)->create()->each(
            function ($balance) use ($newEntity) {
                $balance->entity_id = $newEntity->id;
                $balance->save();
            }
        );

        $this->assertEquals(count(Balance::all()), 0);

        $user = factory(User::class)->create();
        $user->entity()->associate($newEntity);
        $user->save();

        $this->be($user);

        $newEntity->currency_id = factory(Currency::class)->create()->id;
        $newEntity->save();

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
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
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

        factory(Balance::class)->create([
            'transaction_type' => Transaction::CN,
        ]);
    }

    /**
     * Test Wrong Balance Type.
     *
     * @return void
     */
    public function testInvalidBalanceType()
    {
        $this->expectException(InvalidBalanceType::class);
        $this->expectExceptionMessage('Opening Balance Type must be one of: Debit, Credit');

        factory(Balance::class)->create([
            "balance_type" => "X"
        ]);
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

        factory(Balance::class)->create([
            "balance" => -100
        ]);
    }

    /**
     * Test Invalid Balance Date.
     *
     * @return void
     */
    public function testInvalidBalanceDate()
    {
        $entity = Auth::user()->entity;
        
        $entity->mid_year_balances = true;
        $entity->save();

        //no exception
        $balance = factory(Balance::class)->create([
            "transaction_date" => Carbon::now(),
            "entity_id" => $entity->id
        ]);

        $entity->mid_year_balances = false;
        $entity->save();

        $balance->load('entity');
        
        $this->expectException(InvalidBalanceDate::class);
        $this->expectExceptionMessage('Transaction date must be earlier than the first day of the Balance\'s Reporting Period ');

        $balance->save();
    }

    /**
     * Test Invalid Balance Currency.
     *
     * @return void
     */
    public function testInvalidBalanceCurrency()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
            'category_id' => null,
            'currency_id' => factory(Currency::class)->create([
                'currency_code' => 'EUR'
            ])->id
        ]);

        $this->expectException(InvalidCurrency::class);
        $this->expectExceptionMessage('Balance Currency must be the same as the Bank Account Currency ');

        factory(Balance::class)->create([
            "account_id" => $account->id
        ]);
    }
}
