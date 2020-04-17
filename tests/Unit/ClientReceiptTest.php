<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Tests\TestCase;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Currency;
use App\Models\Ledger;
use App\Models\LineItem;

use App\Transactions\ClientReceipt;

use App\Exceptions\LineItemAccount;
use App\Exceptions\MainAccount;
use App\Exceptions\VatCharge;

class ClientReceiptTest extends TestCase
{
    /**
     * Test Creating ClientReceipt Transaction
     *
     * @return void
     */
    public function testCreateClientReceiptTransaction()
    {
        $clientAccount = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);

        $clientReceipt = ClientReceipt::new($clientAccount, Carbon::now(), $this->faker->word);
        $clientReceipt->save();

        $this->assertEquals($clientReceipt->getAccount()->name, $clientAccount->name);
        $this->assertEquals($clientReceipt->getAccount()->description, $clientAccount->description);
        $this->assertEquals($clientReceipt->getTransactionNo(), "RC0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting ClientReceipt Transaction
     *
     * @return void
     */
    public function testPostClientReceiptTransaction()
    {
        $clientReceipt = ClientReceipt::new(
            factory('App\Models\Account')->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('App\Models\Vat')->create([
                "rate" => 0
            ])->id,
            "account_id" => factory('App\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();

        $debit = Ledger::where("entry_type", Balance::D)->get()[0];
        $credit = Ledger::where("entry_type", Balance::C)->get()[0];

        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->folio_account, $clientReceipt->getAccount()->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $clientReceipt->getAccount()->id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $this->assertEquals($clientReceipt->getAmount(), 100);
    }

    /**
     * Test Client Receipt Line Item Account.
     *
     * @return void
     */
    public function testClientReceiptLineItemAccount()
    {
        $clientReceipt = ClientReceipt::new(
            factory('App\Models\Account')->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Client Receipt LineItem Account must be of type Bank');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('App\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('App\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();
    }

    /**
     * Test Client Receipt Main Account.
     *
     * @return void
     */
    public function testClientReceiptMainAccount()
    {
        $clientReceipt = ClientReceipt::new(
            factory('App\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Client Receipt Main Account must be of type Receivable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('App\Models\Vat')->create([
                "rate" => 0
            ])->id,
            "account_id" => factory('App\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();
    }

    /**
     * Test Client Receipt Vat Charge Account.
     *
     * @return void
     */
    public function testClientReceiptVatCharge()
    {
        $clientReceipt = ClientReceipt::new(
            factory('App\Models\Account')->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(VatCharge::class);
        $this->expectExceptionMessage('Client Receipt LineItems cannot be Charged VAT');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('App\Models\Vat')->create([
                "rate" => 10
            ])->id,
            "account_id" => factory('App\Models\Account')->create([
                "account_type" => Account::BANK
            ])->id,
        ]);
        $clientReceipt->addLineItem($lineItem);

        $clientReceipt->post();
    }

    /**
     * Test Client Receipt Find.
     *
     * @return void
     */
    public function testClientReceiptFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction = ClientReceipt::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = ClientReceipt::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
    }

    /**
     * Test Client Receipt Fetch.
     *
     * @return void
     */
    public function testClientReceiptFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction = ClientReceipt::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction2 = ClientReceipt::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(ClientReceipt::fetch()), 2);
        $this->assertEquals(count(ClientReceipt::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(ClientReceipt::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(ClientReceipt::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(ClientReceipt::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $this->assertEquals(count(ClientReceipt::fetch(null, null, $account)), 1);
        $this->assertEquals(count(ClientReceipt::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(ClientReceipt::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(ClientReceipt::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(ClientReceipt::fetch(null, null, null, $currency)), 0);
    }
}
