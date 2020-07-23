<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;


use Carbon\Carbon;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;
use IFRS\Models\Vat;

use IFRS\Transactions\JournalEntry;

class LedgerTest extends TestCase
{
    /**
     * Ledger Model relationships test.
     *
     * @return void
     */
    public function testLedgerRelationships()
    {
        $account = factory(Account::class)->create();
        $lineAccount = factory(Account::class)->create();
        $vat = factory(Vat::class)->create(["rate" => 0]);

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "account_id" => $lineAccount->id,
            "amount" => 50,
            "vat_id" => $vat->id,
        ]);

        $transaction->addLineItem($lineItem);
        $transaction->post();

        $ledger = Ledger::where("entry_type", Balance::CREDIT)->first();

        $this->assertEquals($ledger->transaction->transaction_no, $transaction->transaction_no);
        $this->assertEquals($ledger->postAccount->name, $account->name);
        $this->assertEquals($ledger->folioAccount->name, $lineAccount->name);
        $this->assertEquals($ledger->lineItem->id, $lineItem->id);
    }

    /**
     * Ledger Model Account Contribution test.
     *
     * @return void
     */
    public function testLedgerAccountContribution()
    {
        $account = factory(Account::class)->create();
        $lineAccount1 = factory(Account::class)->create();
        $lineAccount2 = factory(Account::class)->create();
        $vat = factory(Vat::class)->create(["rate" => 0]);

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "account_id" => $lineAccount1->id,
            "amount" => 75,
            "vat_id" => $vat->id,
            "quantity" => 1,
        ]);

        $transaction->addLineItem($lineItem1);

        $lineItem2 = factory(LineItem::class)->create([
            "account_id" => $lineAccount2->id,
            "amount" => 120,
            "vat_id" => $vat->id,
            "quantity" => 1,
        ]);

        $transaction->addLineItem($lineItem2);

        $transaction->post();

        $this->assertEquals($transaction->amount, 195);
        $this->assertEquals(Ledger::contribution($lineAccount1, $transaction->id), 75);
        $this->assertEquals(Ledger::contribution($lineAccount2, $transaction->id), 120);
    }

    /**
     * Ledger Model Account Balance test.
     *
     * @return void
     */
    public function testLedgerAccountBalance()
    {
        $account = factory(Account::class)->create();

        factory(Ledger::class, 3)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::DEBIT,
            "date" => Carbon::now(),
            "amount" => 50
        ]);

        factory(Ledger::class, 2)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::CREDIT,
            "date" => Carbon::now(),
            "amount" => 95
        ]);

        $this->assertEquals(Ledger::balance($account, Carbon::now()->startOfYear(), Carbon::now()), -40);
    }
}
