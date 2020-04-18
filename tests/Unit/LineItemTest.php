<?php

namespace Tests\Unit;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\User;
use Ekmungai\IFRS\Models\Vat;
use Ekmungai\IFRS\Models\Transaction;

use Ekmungai\IFRS\Exceptions\MissingVatAccount;
use Ekmungai\IFRS\Exceptions\NegativeAmount;

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
        $vatAccount = factory(Account::class)->create();
        $vat = factory(Vat::class)->create();

        $lineItem = LineItem::new(
            $account,
            $vat,
            50,
            1,
            null,
            $vatAccount
        );
        $lineItem->save();

        $transaction->addLineItem($lineItem);
        $transaction->post();

        $this->assertEquals($lineItem->transaction->transaction_no, $transaction->transaction_no);
        $this->assertEquals($lineItem->account->name, $account->name);
        $this->assertEquals($lineItem->vatAccount->name, $vatAccount->name);
        $this->assertEquals($lineItem->vat->rate, $vat->rate);
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

        $lineItem = LineItem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            100
        );
        $lineItem->save();

        $this->assertEquals(count(LineItem::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(LineItem::all()), 0);
    }

    /**
     * Test Missing Vat Account.
     *
     * @return void
     */
    public function testMissingVatAccount()
    {
        $vat = factory(Vat::class)->create(["rate" => 10]);
        $lineItem = LineItem::new(
            factory(Account::class)->create(),
            $vat,
            100
        );
        $this->expectException(MissingVatAccount::class);
        $this->expectExceptionMessage($vat->name.' LineItem requires a Vat Account');

        $lineItem->save();
    }

    /**
     * Test Negative Amount.
     *
     * @return void
     */
    public function testNegativeAmount()
    {
        $lineItem = LineItem::new(
            factory(Account::class)->create(),
            factory(Vat::class)->create(["rate" => 0]),
            -100
        );
        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('LineItem Amount cannot be negative');

        $lineItem->save();
    }
}
