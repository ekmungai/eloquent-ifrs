<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\RecycledObject;
use IFRS\Models\Vat;
use IFRS\Models\LineItem;

use IFRS\Exceptions\InvalidAccountType;
use IFRS\Exceptions\MultipleVatError;
use IFRS\Exceptions\MissingVatAccount;

class VatTest extends TestCase
{
    /**
     * Test Vat model Entity Scope.
     *
     * @return void
     */
    public function testVatEntityScope()
    {
        $newEntity = factory(Entity::class)->create();
        $newEntity->currency_id = factory(Currency::class)->create()->id;

        $user = factory(User::class)->create();
        $user->entity()->associate($newEntity);
        $user->save();

        $this->be($user);

        $vat = new Vat([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id,
        ]);
        $vat->attributes();
        $vat->save();

        $this->assertEquals(
            $vat->toString(true),
            'Vat: ' . $vat->name . ' (' . $vat->code . ') at ' . number_format($vat->rate, 2) . '%'
        );
        $this->assertEquals(
            $vat->toString(),
            $vat->name . ' (' . $vat->code . ') at ' . number_format($vat->rate, 2) . '%'
        );
        $this->assertEquals(count(Vat::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Vat::all()), 0);
    }

    /**
     * Test Vat Model recylcling
     *
     * @return void
     */
    public function testVatRecycling()
    {
        $vat = Vat::create([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id,
        ]);
        $vat->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($vat->recycled->first(), $recycled);
        $this->assertEquals($recycled->recyclable->id, $vat->id);

        $vat->restore();

        $this->assertEquals(count($vat->recycled()->get()), 0);
        $this->assertEquals($vat->deleted_at, null);

        //'hard' delete
        $vat->forceDelete();

        $this->assertEquals(count(Vat::all()), 0);
        $this->assertEquals(count(Vat::withoutGlobalScopes()->get()), 1);
        $this->assertNotEquals($vat->deleted_at, null);
        $this->assertNotEquals($vat->destroyed_at, null);

        //destroyed objects cannot be restored
        $vat->restore();

        $this->assertNotEquals($vat->deleted_at, null);
        $this->assertNotEquals($vat->destroyed_at, null);
    }

    /**
     * Test Missing Vat Account.
     *
     * @return void
     */
    public function testMissingVatAccount()
    {
        $vat = new Vat([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
        ]);
        $this->expectException(MissingVatAccount::class);
        $this->expectExceptionMessage($vat->rate . '% VAT requires a Vat Account');

        $vat->save();
    }

    /**
     * Test Invalid Vat Account Type.
     *
     * @return void
     */
    public function testInvalidVatAccountType()
    {
        $vat = new Vat([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
        ]);
        $this->expectException(InvalidAccountType::class);
        $this->expectExceptionMessage('Vat Account must be of Type Control ');

        $vat->save();
    }

    /**
     * Test Apply Multiple Vat.
     *
     * @return void
     */
    public function testApplyMultipleVat()
    {
        $gst = factory(Vat::class)->create([
            'name' => 'GST',
            'rate' => 5,
            'account_id' => factory(Account::class)->create([
                'name' => 'GST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);
        
        $pst = factory(Vat::class)->create([
            'name' => 'PST',
            'rate' => 7,
            'account_id' => factory(Account::class)->create([
                'name' => 'PST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);
        
        $zero = factory(Vat::class)->create([
            'name' => 'Zero Rate',
            'rate' => 0
        ]);

        $lineItem = factory(LineItem::class)->create([
            'amount' => 50,
            'quantity' => 2,
            'vat_id' => $zero->id
        ]);

        # Test simple multiple tax
        $vatItems = Vat::applyMultiple([$gst->id, $pst->id], $lineItem->id);

        $this->assertEquals($vatItems[0]->account->name, 'GST Control Account');
        $this->assertEquals($vatItems[0]->narration, '5% GST Tax on 100');
        $this->assertEquals($vatItems[0]->amount, 5);

        $this->assertEquals($vatItems[1]->account->name, 'PST Control Account');
        $this->assertEquals($vatItems[1]->narration, '7% PST Tax on 100');
        $this->assertEquals($vatItems[1]->amount, 7);

        # Test compound multiple tax
        $vatItems = Vat::applyMultiple([$gst->id, $pst->id], $lineItem->id, true);

        $this->assertEquals($vatItems[0]->account->name, 'GST Control Account');
        $this->assertEquals($vatItems[0]->narration, '5% GST Tax on 100');
        $this->assertEquals($vatItems[0]->amount, 5);

        $this->assertEquals($vatItems[1]->account->name, 'PST Control Account');
        $this->assertEquals($vatItems[1]->narration, '7% PST Tax on 105');
        $this->assertEquals($vatItems[1]->amount, 7.35);
    }

    /**
     * Test Apply Multiple Vat minimum Ids.
     *
     * @return void
     */
    public function testApplyMultipleVatMinimumIds()
    {
        $gst = factory(Vat::class)->create([
            'name' => 'GST',
            'rate' => 5,
            'account_id' => factory(Account::class)->create([
                'name' => 'GST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);
        $lineItem = factory(LineItem::class)->create([
            'amount' => 50,
            'quantity' => 2,
        ]);
        
        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('There must be at least two Vat Ids ');

        Vat::applyMultiple([$gst->id], $lineItem->id);
    }

    /**
     * Test Apply Multiple Vat Zero Amount.
     *
     * @return void
     */
    public function testApplyMultipleVatZeroAmount()
    {
        $gst = factory(Vat::class)->create([
            'name' => 'GST',
            'rate' => 5,
            'account_id' => factory(Account::class)->create([
                'name' => 'GST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);
        
        $pst = factory(Vat::class)->create([
            'name' => 'PST',
            'rate' => 7,
            'account_id' => factory(Account::class)->create([
                'name' => 'PST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);
        
        $zero = factory(Vat::class)->create([
            'name' => 'Zero Rate',
            'rate' => 0
        ]);

        $lineItem = factory(LineItem::class)->create([
            'amount' => 0,
            'vat_id' => $zero->id
        ]);

        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('Line Item amount must be non zero ');

        Vat::applyMultiple([$gst->id, $pst->id], $lineItem->id);
    }    

    /**
     * Test Apply Multiple Vat missing Zero rated Vat.
     *
     * @return void
     */
    public function testApplyMultipleVatMissingZeroRatedVat()
    {
        $gst = factory(Vat::class)->create([
            'name' => 'GST',
            'rate' => 5,
            'account_id' => factory(Account::class)->create([
                'name' => 'GST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);
        
        $pst = factory(Vat::class)->create([
            'name' => 'PST',
            'rate' => 7,
            'account_id' => factory(Account::class)->create([
                'name' => 'PST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);

        $lineItem = factory(LineItem::class)->create([
            'amount' => 100,
            'quantity' => 1
        ]);

        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('VAT Line Items require a Zero rated Vat object ');

        Vat::applyMultiple([$gst->id, $pst->id], $lineItem->id);
    }

    /**
     * Test Apply Multiple Vat Zero Rate.
     *
     * @return void
     */
    public function testApplyMultipleVatZeroRate()
    {
        $gst = factory(Vat::class)->create([
            'name' => 'GST',
            'rate' => 5,
            'account_id' => factory(Account::class)->create([
                'name' => 'GST Control Account',
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id
        ]);
        
        $zero = factory(Vat::class)->create([
            'name' => 'Zero Rate',
            'rate' => 0
        ]);

        
        $lineItem = factory(LineItem::class)->create([
            'amount' => 100,
            'vat_id' => $zero->id
        ]);

        $this->assertEquals(LineItem::all()->count(), 1);
        $this->expectException(MultipleVatError::class);
        $this->expectExceptionMessage('Zero rated taxes cannot be applied ');

        Vat::applyMultiple([$gst->id, $zero->id], $lineItem->id);

        $this->assertEquals(LineItem::all()->count(), 1);
    }
}
