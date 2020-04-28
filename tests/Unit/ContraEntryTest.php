<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\Ledger;
use Ekmungai\IFRS\Models\LineItem;

use Ekmungai\IFRS\Transactions\ContraEntry;

use Ekmungai\IFRS\Exceptions\LineItemAccount;
use Ekmungai\IFRS\Exceptions\MainAccount;

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

        $contraEntry = ContraEntry::new($bankAccount, Carbon::now(), $this->faker->word);
        $contraEntry->setDate(Carbon::now());
        $contraEntry->save();

        $this->assertEquals($contraEntry->getAccount()->name, $bankAccount->name);
        $this->assertEquals($contraEntry->getAccount()->description, $bankAccount->description);
        $this->assertEquals($contraEntry->getTransactionNo(), "CE0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting ContraEntry Transaction
     *
     * @return void
     */
    public function testPostContraEntryTransaction()
    {
        $contraEntry = ContraEntry::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
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
        $contraEntry->addLineItem($lineItem);

        $contraEntry->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $contraEntry->getAccount()->id);
        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $contraEntry->getAccount()->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $this->assertEquals($contraEntry->getAmount(), 100);
    }

    /**
     * Test Contra Entry Line Item Account.
     *
     * @return void
     */
    public function testContraEntryLineItemAccount()
    {
        $contraEntry = ContraEntry::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Contra Entry LineItem Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
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
        $contraEntry = ContraEntry::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Contra Entry Main Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 0
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $contraEntry->addLineItem($lineItem);

        $contraEntry->post();
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
        $transaction = ContraEntry::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = ContraEntry::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
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
        $transaction = ContraEntry::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::BANK,
        ]);
        $transaction2 = ContraEntry::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
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
