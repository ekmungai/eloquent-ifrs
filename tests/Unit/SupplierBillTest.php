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

use Ekmungai\IFRS\Transactions\SupplierBill;

use Ekmungai\IFRS\Exceptions\LineItemAccount;
use Ekmungai\IFRS\Exceptions\MainAccount;

class SupplierBillTest extends TestCase
{
    /**
     * Test Creating SupplierBill Transaction
     *
     * @return void
     */
    public function testCreateSupplierBillTransaction()
    {
        $supplierAccount = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);

        $supplierBill = SupplierBill::new($supplierAccount, Carbon::now(), $this->faker->word);
        $supplierBill->setDate(Carbon::now());
        $supplierBill->save();

        $this->assertEquals($supplierBill->getAccount()->name, $supplierAccount->name);
        $this->assertEquals($supplierBill->getAccount()->description, $supplierAccount->description);
        $this->assertEquals($supplierBill->getTransactionNo(), "BL0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting SupplierBill Transaction
     *
     * @return void
     */
    public function testPostSupplierBillTransaction()
    {
        $supplierBill = SupplierBill::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::DIRECT_EXPENSE
            ])->id,
        ]);
        $supplierBill->addLineItem($lineItem);

        $supplierBill->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->folio_account, $supplierBill->getAccount()->id);
        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $supplierBill->getAccount()->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->folio_account, $supplierBill->getAccount()->id);
        $this->assertEquals($vat_debit->post_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_credit->post_account, $supplierBill->getAccount()->id);
        $this->assertEquals($vat_credit->folio_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($supplierBill->getAmount(), 116);
    }

    /**
     * Test Supplier Bill Line Item Account.
     *
     * @return void
     */
    public function testSupplierBillLineItemAccount()
    {
        $supplierBill = SupplierBill::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage(
            "Supplier Bill LineItem Account must be of type "
            ."Operating Expense, Direct Expense, Overhead Expense, "
            ."Other Expense, Non Current Asset, Current Asset, Inventory"
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $supplierBill->addLineItem($lineItem);

        $supplierBill->post();
    }

    /**
     * Test Supplier Bill Main Account.
     *
     * @return void
     */
    public function testSupplierBillMainAccount()
    {
        $supplierBill = SupplierBill::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Supplier Bill Main Account must be of type Payable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $supplierBill->addLineItem($lineItem);

        $supplierBill->post();
    }

    /**
     * Test Supplier Bill Find.
     *
     * @return void
     */
    public function testSupplierBillFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction = SupplierBill::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = SupplierBill::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
    }

    /**
     * Test Supplier Bill Fetch.
     *
     * @return void
     */
    public function testSupplierBillFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction = SupplierBill::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $transaction2 = SupplierBill::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(SupplierBill::fetch()), 2);
        $this->assertEquals(count(SupplierBill::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(SupplierBill::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(SupplierBill::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(SupplierBill::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
        ]);
        $this->assertEquals(count(SupplierBill::fetch(null, null, $account)), 1);
        $this->assertEquals(count(SupplierBill::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(SupplierBill::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(SupplierBill::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(SupplierBill::fetch(null, null, null, $currency)), 0);
    }
}
