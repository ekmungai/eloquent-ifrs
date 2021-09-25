<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;
use IFRS\Models\Vat;

use IFRS\Transactions\SupplierBill;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;

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
            'category_id' => null
        ]);

        $supplierBill = new SupplierBill([
            "account_id" => $supplierAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $supplierBill->save();

        $this->assertEquals($supplierBill->account->name, $supplierAccount->name);
        $this->assertEquals($supplierBill->account->description, $supplierAccount->description);
        $this->assertEquals($supplierBill->transaction_no, "BL0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting SupplierBill Transaction
     *
     * @return void
     */
    public function testPostSupplierBillTransaction()
    {
        $supplierBill = new SupplierBill([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
        $supplierBill->addLineItem($lineItem);

        $supplierBill->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->folio_account, $supplierBill->account->id);
        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $supplierBill->account->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->folio_account, $supplierBill->account->id);
        $this->assertEquals($vat_debit->post_account, $lineItem->appliedVats[0]->vat->account_id);
        $this->assertEquals($vat_credit->post_account, $supplierBill->account->id);
        $this->assertEquals($vat_credit->folio_account, $lineItem->appliedVats[0]->vat->account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($supplierBill->amount, 116);
    }

    /**
     * Test Supplier Bill Line Item Account.
     *
     * @return void
     */
    public function testSupplierBillLineItemAccount()
    {
        $supplierBill = new SupplierBill([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage(
            "Supplier Bill LineItem Account must be of type "
                . "Operating Expense, Direct Expense, Overhead Expense, "
                . "Other Expense, Non Current Asset, Current Asset, Inventory"
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
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
        $supplierBill = new SupplierBill([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Supplier Bill Main Account must be of type Payable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null
            ])->id,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();
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
            'category_id' => null
        ]);
        $transaction = new SupplierBill([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $transaction->save();

        $found = SupplierBill::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
    }
}
