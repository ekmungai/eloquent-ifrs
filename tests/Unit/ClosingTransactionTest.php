<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\ClosingTransaction;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Transaction;

class ClosingTransactionTest extends TestCase
{
    /**
     * Closing Transaction Model relationships test.
     *
     * @return void
     */
    public function testclosingTransactionRelationships()
    {
        $transaction = factory(Transaction::class)->create([
            'transaction_type' => Transaction::JN
        ]);
        $reportingPeriod = factory(ReportingPeriod::class)->create();
        $currency = factory(Currency::class)->create();

        $closingTransaction = ClosingTransaction::create([
            'transaction_id' => $transaction->id,
            'reporting_period_id' => $reportingPeriod->id,
            'currency_id' => $currency->id,
        ]);

        $closingTransaction->attributes();

        $this->assertEquals($closingTransaction->transaction->id, $transaction->id);
        $this->assertEquals($closingTransaction->transaction->account_id, $transaction->account_id);
        $this->assertEquals($closingTransaction->reportingPeriod->calendar_year, $reportingPeriod->calendar_year);
        $this->assertEquals($closingTransaction->currency->currency_code, $currency->currency_code);
        
        $this->assertEquals(
            $closingTransaction->toString(true),
            'ClosingTransaction: ' . $reportingPeriod->calendar_year . ' Forex Translation Transaction ' . $transaction->toString() 
        );
        $this->assertEquals(
            $closingTransaction->toString(),
            $reportingPeriod->calendar_year . ' Forex Translation Transaction ' . $transaction->toString() 
        );
    }

    /**
     * Test ClosingTransaction model Entity Scope.
     *
     * @return void
     */
    public function testClosingTransactionEntityScope()
    {
        $newEntity = factory(Entity::class)->create();

        $user = factory(User::class)->create();
        $user->entity()->associate($newEntity);
        $user->save();

        $this->be($user);

        $newEntity->currency()->associate(factory(Currency::class)->create());
        $newEntity->save();

        $this->period = factory(ReportingPeriod::class)->create([
            "calendar_year" => date("Y"),
        ]);

        ClosingTransaction::create([
            'transaction_id' => factory(Transaction::class)->create([
                'transaction_type' => Transaction::JN
            ])->id,
            'reporting_period_id' => factory(ReportingPeriod::class)->create()->id,
            'currency_id' => factory(Currency::class)->create()->id,
        ]);

        $this->assertEquals(count(ClosingTransaction::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(ClosingTransaction::all()), 0);
    }

    /**
     * Test ClosingTransaction Model recylcling
     *
     * @return void
     */
    public function testClosingTransactionRecycling()
    {
        $transaction = ClosingTransaction::create([
            'transaction_id' => factory(Transaction::class)->create([
                'transaction_type' => Transaction::JN
            ])->id,
            'reporting_period_id' => factory(ReportingPeriod::class)->create()->id,
            'currency_id' => factory(Currency::class)->create()->id,
        ]);
        
        $transaction->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($transaction->recycled->first(), $recycled);
    }
}
