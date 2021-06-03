<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use Illuminate\Support\Facades\Auth;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;

use IFRS\Transactions\SupplierPayment;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;
use IFRS\Models\Vat;

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
            'category_id' => null
        ]);

        $supplierPayment = new SupplierPayment([
            "account_id" => $supplierAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $supplierPayment->save();

        $this->assertEquals($supplierPayment->account->name, $supplierAccount->name);
        $this->assertEquals($supplierPayment->account->description, $supplierAccount->description);
        $this->assertEquals($supplierPayment->transaction_no, "PY0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting SupplierPayment Transaction
     *
     * @return void
     */
    public function testPostSupplierPaymentTransaction()
    {
        $currency = factory(Currency::class)->create();
        $supplierPayment = new SupplierPayment([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            "quantity" => 1,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->post_account, $supplierPayment->account->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $supplierPayment->account->id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $this->assertEquals($supplierPayment->amount, 100);
    }

    /**
     * Test Supplier Payment Line Item Account.
     *
     * @return void
     */
    public function testSupplierPaymentLineItemAccount()
    {
        $supplierPayment = new SupplierPayment([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Supplier Payment LineItem Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION,
                'category_id' => null
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
        $currency = factory(Currency::class)->create();
        $supplierPayment = new SupplierPayment([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $currency->id,
        ]);
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Supplier Payment Main Account must be of type Payable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
        ]);
        $supplierPayment->addLineItem($lineItem);

        $supplierPayment->save();
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
            'category_id' => null
        ]);
        $transaction = new SupplierPayment([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $found = SupplierPayment::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
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
            'category_id' => null
        ]);
        $transaction = new SupplierPayment([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null
        ]);
        $transaction2 = new SupplierPayment([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now()->addWeeks(2),
            "narration" => $this->faker->word,
        ]);
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
            'category_id' => null
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
