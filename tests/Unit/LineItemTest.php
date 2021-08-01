<?php

namespace Tests\Unit;

use Carbon\Carbon;
use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\LineItem;
use IFRS\User;
use IFRS\Models\Vat;
use IFRS\Models\Transaction;

use IFRS\Exceptions\NegativeAmount;
use IFRS\Exceptions\NegativeQuantity;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Transactions\ClientInvoice;

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
        $account = factory(Account::class)->create([
            'category_id' => null
        ]);
        $vatAccount = factory(Account::class)->create([
            'account_type' => Account::CONTROL,
            'category_id' => null
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
        $newEntity = factory(Entity::class)->create();

        $user = factory(User::class)->create();
        $user->entity()->associate($newEntity);
        $user->save();

        $this->be($user);

        $newEntity->currency_id = factory(Currency::class)->create()->id;
        $newEntity->save();

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
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
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => -100,
        ]);
        $this->expectException(NegativeAmount::class);
        $this->expectExceptionMessage('LineItem Amount cannot be negative');

        $lineItem->save();
    }

    /**
     * Test Negative Quantity.
     *
     * @return void
     */
    public function testNegativeQuantity()
    {
        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'amount' => 100,
            'quantity' => -1,
        ]);
        $this->expectException(NegativeQuantity::class);
        $this->expectExceptionMessage('LineItem Quantity cannot be negative');

        $lineItem->save();
    }

    /**
     * Test Tax Inclusive Amount.
     *
     * @return void
     */
    public function testTaxInclusiveAmount()
    {
        $revenueAccount = factory(Account::class)->create([
            "account_type" => Account::OPERATING_REVENUE,
            'category_id' => null
        ]);
        $vat = factory(Vat::class)->create([
            "rate" => 16
        ]);

        $clientInvoice = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => $vat->id,
            "account_id" => $revenueAccount->id,
            "quantity" => 1,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        $this->assertEquals($clientInvoice->amount, 116);
        $this->assertEquals($clientInvoice->account->closingBalance(), [$this->reportingCurrencyId => 116]);
        $this->assertEquals($revenueAccount->closingBalance(), [$this->reportingCurrencyId => -100]);
        $this->assertEquals($vat->account->closingBalance(), [$this->reportingCurrencyId => -16]);

        $revenueAccount2 = factory(Account::class)->create([
            "account_type" => Account::OPERATING_REVENUE,
            'category_id' => null
        ]);
        $vat2 = factory(Vat::class)->create([
            "rate" => 16
        ]);

        $clientInvoice2 = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem2 = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => $vat2->id,
            "account_id" => $revenueAccount2->id,
            "vat_inclusive" => true,
            "quantity" => 1,
        ]);
        $clientInvoice2->addLineItem($lineItem2);

        $clientInvoice2->post();

        $this->assertEquals($clientInvoice2->amount, 100);
        $this->assertEquals($clientInvoice2->account->closingBalance()[$this->reportingCurrencyId], 100);
        $this->assertEquals(round($revenueAccount2->closingBalance()[$this->reportingCurrencyId], 2), -86.21);
        $this->assertEquals(round($vat2->account->closingBalance()[$this->reportingCurrencyId], 2), -13.79);
    }
}
