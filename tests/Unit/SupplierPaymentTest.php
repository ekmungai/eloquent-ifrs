<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Ekmungai\IFRS\Tests\TestCase;

use Illuminate\Support\Facades\Auth;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\Ledger;
use Ekmungai\IFRS\Models\LineItem;

use Ekmungai\IFRS\Transactions\SupplierPayment;

use Ekmungai\IFRS\Exceptions\LineItemAccount;
use Ekmungai\IFRS\Exceptions\MainAccount;

class SupplierPaymentTest extends TestCase
{
    /**
     * Test Creating SupplierPayment Transaction
     *
     * @return void
     */
    public function testCreateSupplierPaymentTransaction()
    {
        $supplierAccount = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);

        $supplierPayment = SupplierPayment::new($supplierAccount, Carbon::now(), $this->faker->word);
        $supplierPayment->setDate(Carbon::now());
        $supplierPayment->save();

        $this->assertEquals($supplierPayment->getAccount()->name, $supplierAccount->name);
        $this->assertEquals($supplierPayment->getAccount()->description, $supplierAccount->description);
        $this->assertEquals($supplierPayment->getTransactionNo(), "PY0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting SupplierPayment Transaction
     *
     * @return void
     */
    public function testPostSupplierPaymentTransaction()
    {
        $supplierPayment = SupplierPayment::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->post_account, $supplierPayment->getAccount()->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $supplierPayment->getAccount()->id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $this->assertEquals($supplierPayment->getAmount(), 100);
    }

    /**
     * Test Supplier Payment Line Item Account.
     *
     * @return void
     */
    public function testSupplierPaymentLineItemAccount()
    {
        $supplierPayment = SupplierPayment::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Supplier Payment LineItem Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();
    }

    /**
     * Test Supplier Payment Main Account.
     *
     * @return void
     */
    public function testSupplierPaymentMainAccount()
    {
        $supplierPayment = SupplierPayment::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Supplier Payment Main Account must be of type Payable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();
    }

    /**
     * Test Supplier Payment Find.
     *
     * @return void
     */
    public function testSupplierPaymentFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction = SupplierPayment::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = SupplierPayment::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
    }

    /**
     * Test Supplier Payment Fetch.
     *
     * @return void
     */
    public function testSupplierPaymentFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction = SupplierPayment::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction2 = SupplierPayment::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(SupplierPayment::fetch()), 2);
        $this->assertEquals(count(SupplierPayment::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(SupplierPayment::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(SupplierPayment::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(SupplierPayment::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $this->assertEquals(count(SupplierPayment::fetch(null, null, $account)), 1);
        $this->assertEquals(count(SupplierPayment::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(SupplierPayment::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(SupplierPayment::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(SupplierPayment::fetch(null, null, null, $currency)), 0);
    }
}
