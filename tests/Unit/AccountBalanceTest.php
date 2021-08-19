<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Faker\Factory;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;
use IFRS\Models\Transaction;
use IFRS\Models\Entity;

use IFRS\Exceptions\InvalidBalanceTransaction;
use IFRS\Exceptions\InvalidAccountClassBalance;
use IFRS\Exceptions\NegativeAmount;
use IFRS\Exceptions\InvalidBalanceType;
use IFRS\Exceptions\InvalidBalanceDate;
use IFRS\Exceptions\InvalidCurrency;

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

    public function testBalanceRelationshipsLoggedOut()
    {
        $faker = Factory::create();

        $entity = Auth::user()->entity;
        Auth::logout();

        $account = Account::create([
            'account_type' => Account::INVENTORY,
            'category_id' => null,
            'entity_id' => $entity->id
        ]);

        $exchangeRate = ExchangeRate::create([
            'valid_from' => $faker->dateTimeThisMonth(),
            'valid_to' => Carbon::now(),
            'currency_id' => Currency::create([
                'name' => $faker->name,
                'currency_code' => $faker->currencyCode,
                'entity_id' => $entity->id
            ])->id,
            'rate' => 1,
            'entity_id' => $entity->id
        ]);

        $currency = factory(Currency::class)->create([
            'currency_code' => 'EUR',
            'entity_id' => $entity->id
        ]);

        $balance = new Balance([
            'exchange_rate_id' => $exchangeRate->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'transaction_type' => Transaction::JN,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'reference' => $faker->word,
            'balance_type' =>  Balance::DEBIT,
            'amount' => $faker->randomFloat(2),
            'entity_id' => $entity->id
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

    public function testBalanceAccountCurrencyLoggedOut()
    {
        $faker = Factory::create();

        $entity = Auth::user()->entity;
        Auth::logout();

        $account = Account::create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'entity_id' => $entity->id
        ]);

        $exchangeRate = ExchangeRate::create([
            'valid_from' => $faker->dateTimeThisMonth(),
            'valid_to' => Carbon::now(),
            'currency_id' => Currency::create([
                'name' => $faker->name,
                'currency_code' => $faker->currencyCode,
                'entity_id' => $entity->id
            ])->id,
            'rate' => 105,
            'entity_id' => $entity->id
        ]);

        $balance = new Balance([
            'exchange_rate_id' => $exchangeRate->id,
            'account_id' => $account->id,
            'transaction_type' => Transaction::JN,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'reference' => $this->faker->word,
            'currency_id' => Currency::create([
                'name' => $faker->name,
                'currency_code' => 'USD',
                'entity_id' => $entity->id
            ])->id,
            'balance_type' =>  Balance::DEBIT,
            'balance' => 50,
            'entity_id' => $entity->id
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

    public function testBalanceRecyclingLoggedOut()
    {
        $faker = Factory::create();

        $entity = Auth::user()->entity;
        Auth::logout();

        $balance = new Balance([
            'exchange_rate_id' => ExchangeRate::create([
                'valid_from' => $faker->dateTimeThisMonth(),
                'valid_to' => Carbon::now(),
                'currency_id' => Currency::create([
                    'name' => $faker->name,
                    'currency_code' => $faker->currencyCode,
                    'entity_id' => $entity->id
                ])->id,
                'rate' => 105,
                'entity_id' => $entity->id
            ])->id,
            'account_id' =>   $account = Account::create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null,
                'entity_id' => $entity->id
            ])->id,
            'transaction_type' => Transaction::JN,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'reference' => $this->faker->word,
            'currency_id' => Currency::create([
                'name' => $faker->name,
                'currency_code' => 'USD',
                'entity_id' => $entity->id
            ])->id,
            'balance_type' =>  Balance::DEBIT,
            'balance' => 50,
            'entity_id' => $entity->id
        ]);

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

    public function testInvalidAccountClassBalanceLoggedOut()
    {
        $entity = Auth::user()->entity;
        Auth::logout();

        $this->expectException(InvalidAccountClassBalance::class);
        $this->expectExceptionMessage('Income Statement Accounts cannot have Opening Balances');

        Balance::create([
            'account_id' => Account::create([
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => null ,
                'entity_id' => $entity->id
            ])->id,
            'balance_type' => Balance::CREDIT,
            'exchange_rate_id' => ExchangeRate::create([
                'valid_from' => $this->faker->dateTimeThisMonth(),
                'valid_to' => Carbon::now(),
                'currency_id' => Currency::create([
                    'name' => $this->faker->name,
                    'currency_code' => $this->faker->currencyCode,
                    'entity_id' => $entity->id
                ])->id,
                'rate' => 1,
                'entity_id' => $entity->id
            ])->id,
            'reporting_period_id' => $this->period->id,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'transaction_no' => $this->faker->word,
            'transaction_type' => $this->faker->randomElement([
                Transaction::IN,
                Transaction::BL,
                Transaction::JN
            ]),
            'reference' => $this->faker->word,
            'balance' => 100,
            'entity_id' => $entity->id

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

    public function testInvalidBalanceTransactionLoggedOut()
    {
        $entity = Auth::user()->entity;
        Auth::logout();

        $this->expectException(InvalidBalanceTransaction::class);
        $this->expectExceptionMessage(
            'Opening Balance Transaction must be one of: Client Invoice, Supplier Bill, Journal Entry'
        );

        Balance::create([
            'account_id' => Account::create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null ,
                'entity_id' => $entity->id
            ])->id,
            'balance_type' => Balance::CREDIT,
            'exchange_rate_id' => ExchangeRate::create([
                'valid_from' => $this->faker->dateTimeThisMonth(),
                'valid_to' => Carbon::now(),
                'currency_id' => Currency::create([
                    'name' => $this->faker->name,
                    'currency_code' => $this->faker->currencyCode,
                    'entity_id' => $entity->id
                ])->id,
                'rate' => 1,
                'entity_id' => $entity->id
            ])->id,
            'reporting_period_id' => $this->period->id,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'transaction_no' => $this->faker->word,
            'transaction_type' => Transaction::CN,
            'reference' => $this->faker->word,
            'balance' => 100,
            'entity_id' => $entity->id

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

    public function testInvalidBalanceTypeLoggedOut()
    {
        $entity = Auth::user()->entity;
        Auth::logout();

        $this->expectException(InvalidBalanceType::class);
        $this->expectExceptionMessage('Opening Balance Type must be one of: Debit, Credit');

        Balance::create([
            'account_id' => Account::create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null ,
                'entity_id' => $entity->id
            ])->id,
            'balance_type' => 'X',
            'exchange_rate_id' => ExchangeRate::create([
                'valid_from' => $this->faker->dateTimeThisMonth(),
                'valid_to' => Carbon::now(),
                'currency_id' => Currency::create([
                    'name' => $this->faker->name,
                    'currency_code' => $this->faker->currencyCode,
                    'entity_id' => $entity->id
                ])->id,
                'rate' => 1,
                'entity_id' => $entity->id
            ])->id,
            'reporting_period_id' => $this->period->id,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'transaction_no' => $this->faker->word,
            'transaction_type' => Transaction::IN,
            'reference' => $this->faker->word,
            'balance' => 100,
            'entity_id' => $entity->id

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

    public function testBalanceNegativeAmountLoggedOut()
    {
        $entity = Auth::user()->entity;
        Auth::logout();

        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('Balance Amount cannot be negative');

        Balance::create([
            'account_id' => Account::create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null ,
                'entity_id' => $entity->id
            ])->id,
            'balance_type' => Balance::CREDIT,
            'exchange_rate_id' => ExchangeRate::create([
                'valid_from' => $this->faker->dateTimeThisMonth(),
                'valid_to' => Carbon::now(),
                'currency_id' => Currency::create([
                    'name' => $this->faker->name,
                    'currency_code' => $this->faker->currencyCode,
                    'entity_id' => $entity->id
                ])->id,
                'rate' => 1,
                'entity_id' => $entity->id
            ])->id,
            'reporting_period_id' => $this->period->id,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'transaction_no' => $this->faker->word,
            'transaction_type' => $this->faker->randomElement([
                Transaction::IN,
                Transaction::BL,
                Transaction::JN
            ]),
            'reference' => $this->faker->word,
            'balance' => -100,
            'entity_id' => $entity->id

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
            'name' => 'Savings & Loan',
            'account_type' => Account::BANK,
            'category_id' => null,
            'currency_id' => factory(Currency::class)->create([
                'currency_code' => 'EUR'
            ])->id
        ]);

        $this->expectException(InvalidCurrency::class);
        $this->expectExceptionMessage('Balance Currency must be the same as the Bank: Savings & Loan Account Currency ');

        factory(Balance::class)->create([
            "account_id" => $account->id
        ]);
    }
}
