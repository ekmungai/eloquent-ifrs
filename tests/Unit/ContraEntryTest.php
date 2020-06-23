<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;

use IFRS\Transactions\ContraEntry;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;
use IFRS\Models\Vat;

class ContraEntryTest extends TestCase
{
    /**
     * Test Creating ContraEntry Transaction
     *
     * @return void
     */
    public function testCreateContraEntryTransaction()
    {
        $bankAccount = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);

        $contraEntry = new ContraEntry([
            "account_id" => $bankAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $contraEntry->save();

        $this->assertEquals($contraEntry->account->name, $bankAccount->name);
        $this->assertEquals($contraEntry->account->description, $bankAccount->description);
        $this->assertEquals($contraEntry->transaction_no, "CE0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting ContraEntry Transaction
     *
     * @return void
     */
    public function testPostContraEntryTransaction()
    {
        $contraEntry = new ContraEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $contraEntry->addLineItem($lineItem);

        $contraEntry->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $contraEntry->account->id);
        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $contraEntry->account->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $this->assertEquals($contraEntry->amount, 100);
    }

    /**
     * Test Contra Entry Line Item Account.
     *
     * @return void
     */
    public function testContraEntryLineItemAccount()
    {
        $contraEntry = new ContraEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Contra Entry LineItem Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $contraEntry->addLineItem($lineItem);

        $contraEntry->post();
    }

    /**
     * Test Contra Entry Main Account.
     *
     * @return void
     */
    public function testContraEntryMainAccount()
    {
        $contraEntry = new ContraEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Contra Entry Main Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $contraEntry->addLineItem($lineItem);

        $contraEntry->save();
    }

    /**
     * Test Contra Entry Find.
     *
     * @return void
     */
    public function testContraEntryFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction = new ContraEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $found = ContraEntry::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
    }

    /**
     * Test Contra Entry Fetch.
     *
     * @return void
     */
    public function testContraEntryFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction = new ContraEntry([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction2 = new ContraEntry([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now()->addWeeks(2),
            "narration" => $this->faker->word,
        ]);
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(ContraEntry::fetch()), 2);
        $this->assertEquals(count(ContraEntry::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(ContraEntry::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(ContraEntry::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(ContraEntry::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $this->assertEquals(count(ContraEntry::fetch(null, null, $account)), 1);
        $this->assertEquals(count(ContraEntry::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(ContraEntry::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(ContraEntry::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(ContraEntry::fetch(null, null, null, $currency)), 0);
    }
}
