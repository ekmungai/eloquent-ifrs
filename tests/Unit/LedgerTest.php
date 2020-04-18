<?php

namespace Tests\Unit;

use Ekmungai\IFRS\Tests\TestCase;


use Carbon\Carbon;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Ledger;
use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\Vat;

use Ekmungai\IFRS\Transactions\JournalEntry;

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
        $vat = factory(Vat::class)->create(["rate"=>0]);

        $transaction = JournalEntry::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = LineItem::new(
            $lineAccount,
            $vat,
            50
        );
        $lineItem->save();

        $transaction->addLineItem($lineItem);
        $transaction->post();

        $ledger = Ledger::where("entry_type", Balance::C)->first();

        $this->assertEquals($ledger->transaction->transaction_no, $transaction->getTransactionNo());
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
        $vat = factory(Vat::class)->create(["rate"=>0]);

        $transaction = JournalEntry::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );

        $lineItem1 = LineItem::new(
            $lineAccount1,
            $vat,
            75
        );
        $lineItem1->save();

        $transaction->addLineItem($lineItem1);

        $lineItem2 = LineItem::new(
            $lineAccount2,
            $vat,
            120
        );
        $lineItem2->save();

        $transaction->addLineItem($lineItem2);

        $transaction->post();

        $this->assertEquals($transaction->getAmount(), 195);
        $this->assertEquals(Ledger::contribution($lineAccount1, $transaction->getId()), 75);
        $this->assertEquals(Ledger::contribution($lineAccount2, $transaction->getId()), 120);
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
            "entry_type" => Balance::D,
            "date" => Carbon::now(),
            "amount" => 50
        ]);

        factory(Ledger::class, 2)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::C,
            "date" => Carbon::now(),
            "amount" => 95
        ]);

        $this->assertEquals(Ledger::balance($account, Carbon::now()->startOfYear(), Carbon::now()), -40);
    }
}
