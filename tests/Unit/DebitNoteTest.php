<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;
use IFRS\Models\Vat;

use IFRS\Models\Currency;

use IFRS\Transactions\DebitNote;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;

class DebitNoteTest extends TestCase
{
    /**
     * Test Creating DebitNote Transaction
     *
     * @return void
     */
    public function testCreateDebitNoteTransaction()
    {
        $supplierAccount = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null
        ]);

        $debitNote = new DebitNote([
            "account_id" => $supplierAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $debitNote->save();

        $this->assertEquals($debitNote->account->name, $supplierAccount->name);
        $this->assertEquals($debitNote->account->description, $supplierAccount->description);
        $this->assertEquals($debitNote->transaction_no, "DN0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting DebitNote Transaction
     *
     * @return void
     */
    public function testPostDebitNoteTransaction()
    {
        $debitNote = new DebitNote([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $debitNote->addLineItem($lineItem);

        $debitNote->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $debitNote->account->id);
        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $debitNote->account->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->post_account, $debitNote->account->id);
        $this->assertEquals($vat_debit->folio_account, $lineItem->vat->account_id);
        $this->assertEquals($vat_credit->folio_account, $debitNote->account->id);
        $this->assertEquals($vat_credit->post_account, $lineItem->vat->account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($debitNote->amount, 116);
    }

    /**
     * Test Debit Note Line Item Account.
     *
     * @return void
     */
    public function testDebitNoteLineItemAccount()
    {
        $debitNote = new DebitNote([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage(
            "Debit Note LineItem Account must be of type "
            . "Operating Expense, Direct Expense, Overhead Expense, "
            . "Other Expense, Non Current Asset, Current Asset, Inventory"
        );

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
        $debitNote->addLineItem($lineItem);

        $debitNote->post();
    }

    /**
     * Test Debit Note Main Account.
     *
     * @return void
     */
    public function testDebitNoteMainAccount()
    {
        $debitNote = new DebitNote([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Debit Note Main Account must be of type Payable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null
            ])->id,
        ]);
        $debitNote->addLineItem($lineItem);

        $debitNote->post();
    }

    /**
     * Test Debit Note Find.
     *
     * @return void
     */
    public function testDebitNoteFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null
        ]);
        $transaction = new DebitNote([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $found = DebitNote::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
    }
}
