<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\LineItem;
use IFRS\User;
use IFRS\Models\Vat;
use IFRS\Models\Transaction;

use IFRS\Exceptions\NegativeAmount;

class LineItemTest extends TestCase
{
    /**
     * LineItem Model relationships test.
     *
     * @return void
     */
    public function testLineItemRelationships()
    {
        $transaction = factory(Transaction::class)->create();
        $account = factory(Account::class)->create();
        $vatAccount = factory(Account::class)->create([
            'account_type' => Account::CONTROL
        ]);
        $vat = factory(Vat::class)->create([
            'account_id' => $vatAccount->id
        ]);

        $lineItem = new LineItem([
            'vat_id' => $vat->id,
            'account_id' => $account->id,
            'narration' => $this->faker->sentence,
            'quantity' => 1,
            'amount' => 50,
        ]);
        $lineItem->save();
        $lineItem->attributes();

        $transaction->addLineItem($lineItem);
        $transaction->post();

        $lineItem = LineItem::find($lineItem->id);

        $this->assertEquals($lineItem->transaction->transaction_no, $transaction->transaction_no);
        $this->assertEquals($lineItem->account->name, $account->name);
        $this->assertEquals($lineItem->vat->account->name, $vatAccount->name);
        $this->assertEquals($lineItem->vat->rate, $vat->rate);
        $this->assertEquals(
            $lineItem->toString(true),
            'LineItem: ' . $lineItem->account->toString() . ' for 50'
        );
        $this->assertEquals(
            $lineItem->toString(),
            $lineItem->account->toString() . ' for 50'
        );
    }

    /**
     * Test LineItem model Entity Scope.
     *
     * @return void
     */
    public function testLineItemEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => 100,
        ]);
        $lineItem->save();

        $this->assertEquals(count(LineItem::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(LineItem::all()), 0);
    }

    /**
     * Test Negative Amount.
     *
     * @return void
     */
    public function testNegativeAmount()
    {
        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create()->id,
            'amount' => -100,
        ]);
        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('LineItem Amount cannot be negative');

        $lineItem->save();
    }
}
