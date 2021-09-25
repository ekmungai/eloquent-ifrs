<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Account;
use IFRS\Models\LineItem;
use IFRS\Models\Vat;
use IFRS\Models\Transaction;
use IFRS\Models\Currency;
use IFRS\Models\Entity;

use IFRS\Transactions\ClientInvoice;

use IFRS\Exceptions\NegativeAmount;
use IFRS\Exceptions\NegativeQuantity;
use IFRS\Exceptions\MultipleVatError;

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
            'account_id' => $account->id,
            'narration' => $this->faker->sentence,
            'quantity' => 1,
            'amount' => 50,
        ]);

        $lineItem->addVat($vat);
        $lineItem->save();
        $lineItem->attributes();

        $transaction->addLineItem($lineItem);
        $transaction->post();

        $lineItem = LineItem::find($lineItem->id);

        $this->assertEquals($lineItem->transaction->transaction_no, $transaction->transaction_no);
        $this->assertEquals($lineItem->account->name, $account->name);

        $vats = $lineItem->appliedVats;
        $this->assertEquals($vats[0]->vat->account->name, $vatAccount->name);
        $this->assertEquals($vats[0]->vat->rate, $vat->rate);
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
            "account_id" => $revenueAccount->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat($vat);
        $lineItem->save();
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
            "account_id" => $revenueAccount2->id,
            "vat_inclusive" => true,
            "quantity" => 1,
        ]);
        $lineItem2->addVat($vat2);
        $lineItem2->save();
        $clientInvoice2->addLineItem($lineItem2);

        $clientInvoice2->post();

        $this->assertEquals($clientInvoice2->amount, 100);
        $this->assertEquals($clientInvoice2->account->closingBalance()[$this->reportingCurrencyId], 100);
        $this->assertEquals(round($revenueAccount2->closingBalance()[$this->reportingCurrencyId], 2), -86.21);
        $this->assertEquals(round($vat2->account->closingBalance()[$this->reportingCurrencyId], 2), -13.79);
    }

    /**
     * Test Simple Multiple Tax.
     *
     * @return void
     */
    public function testSimpleMultipleTax()
    {
        $revenueAccount = factory(Account::class)->create([
            "account_type" => Account::OPERATING_REVENUE,
            'category_id' => null
        ]);
        $vat = factory(Vat::class)->create([
            "rate" => 5
        ]);
        $vat2 = factory(Vat::class)->create([
            "rate" => 7
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
            "account_id" => $revenueAccount->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat($vat);
        $lineItem->addVat($vat2);
        $lineItem->save();

        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        $this->assertEquals($clientInvoice->amount, 112);
        $this->assertEquals($clientInvoice->account->closingBalance(), [$this->reportingCurrencyId => 112]);
        $this->assertEquals($revenueAccount->closingBalance(), [$this->reportingCurrencyId => -100]);
        $this->assertEquals($vat->account->closingBalance(), [$this->reportingCurrencyId => -5]);
        $this->assertEquals($vat2->account->closingBalance(), [$this->reportingCurrencyId => -7]);
    }

    /**
     * Test Compound Multiple Tax.
     *
     * @return void
     */
    public function testCompoundMultipleTax()
    {
        $revenueAccount = factory(Account::class)->create([
            "account_type" => Account::OPERATING_REVENUE,
            'category_id' => null
        ]);
        $vat = factory(Vat::class)->create([
            "rate" => 5
        ]);
        $vat2 = factory(Vat::class)->create([
            "rate" => 7
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
            "account_id" => $revenueAccount->id,
            "quantity" => 1,
            "compound_vat" => true,
        ]);
        $lineItem->addVat($vat);
        $lineItem->addVat($vat2);
        $lineItem->save();

        $clientInvoice->addLineItem($lineItem);
        
        $clientInvoice->post();

        $this->assertEquals($clientInvoice->amount, 112.35);
        $this->assertEquals($clientInvoice->account->closingBalance(), [$this->reportingCurrencyId => 112.35]);
        $this->assertEquals($revenueAccount->closingBalance(), [$this->reportingCurrencyId => -100]);
        $this->assertEquals($vat->account->closingBalance(), [$this->reportingCurrencyId => -5]);
        $this->assertEquals($vat2->account->closingBalance(), [$this->reportingCurrencyId => -7.35]);
    }

    /**
     * Test Zero Rated Compound Tax.
     *
     * @return void
     */
    public function testZeroRatedCompoundTax()
    {
        $vat = factory(Vat::class)->create([
            "rate" => 0
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "quantity" => 1,
            "compound_vat" => true,
        ]);
        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('Zero rated taxes cannot be applied to a compound vat Line Item');
        
        $lineItem->addVat($vat);
    }

    /**
     * Test Vat Inclusive Compound Tax.
     *
     * @return void
     */
    public function testVatInclusiveCompoundTax()
    {
        $vat = factory(Vat::class)->create([
            "rate" => 5
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "quantity" => 1,
            "compound_vat" => true,
            "vat_inclusive" => true,
        ]);
        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('Vat inclusive Line Items cannot have compound Vat');
        
        $lineItem->addVat($vat);
    }

    /**
     * Test Vat Inclusive Multiple Tax.
     *
     * @return void
     */
    public function testVatInclusiveMultipleTax()
    {
        $vat = factory(Vat::class)->create([
            "rate" => 5
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "quantity" => 1,
            "vat_inclusive" => true,
        ]);

        $lineItem->addVat($vat);

        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('Vat inclusive Line Items cannot have more than one Vat');
        
        $lineItem->addVat($vat);
    }
}
