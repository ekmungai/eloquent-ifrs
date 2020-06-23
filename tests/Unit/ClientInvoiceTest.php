<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\LineItem;
use IFRS\Models\Ledger;

use IFRS\Transactions\ClientInvoice;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;
use IFRS\Models\Vat;

class ClientInvoiceTest extends TestCase
{
    /**
     * Test Creating ClientInvoice Transaction
     *
     * @return void
     */
    public function testCreateClientInvoiceTransaction()
    {
        $clientAccount = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);

        $clientInvoice = new ClientInvoice([
            "account_id" => $clientAccount->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $clientInvoice->save();

        $this->assertEquals($clientInvoice->account->name, $clientAccount->name);
        $this->assertEquals($clientInvoice->account->description, $clientAccount->description);
        $this->assertEquals($clientInvoice->transaction_no, "IN0" . $this->period->period_count . "/0001");
    }

    /**
     * Test Posting ClientInvoice Transaction
     *
     * @return void
     */
    public function testPostClientInvoiceTransaction()
    {
        $clientInvoice = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
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
                "account_type" => Account::OPERATING_REVENUE
            ])->id,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $clientInvoice->account->id);
        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $clientInvoice->account->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->post_account, $clientInvoice->account->id);
        $this->assertEquals($vat_debit->folio_account, $lineItem->vat->account_id);
        $this->assertEquals($vat_credit->folio_account, $clientInvoice->account->id);
        $this->assertEquals($vat_credit->post_account, $lineItem->vat->account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($clientInvoice->amount, 116);
    }

    /**
     * Test Client InvoiceLine Item Account.
     *
     * @return void
     */
    public function testClientInvoiceLineItemAccount()
    {
        $clientInvoice = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Client Invoice LineItem Account must be of type Operating Revenue');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION
            ])->id,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();
    }

    /**
     * Test Client Invoice Main Account.
     *
     * @return void
     */
    public function testClientInvoiceMainAccount()
    {
        $clientInvoice = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECONCILIATION,
            ])->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Client Invoice Main Account must be of type Receivable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE
            ])->id,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();
    }

    /**
     * Test Client Invoice Find.
     *
     * @return void
     */
    public function testClientInvoiceFind()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction = new ClientInvoice([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $found = ClientInvoice::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
    }

    /**
     * Test Client Invoice Fetch.
     *
     * @return void
     */
    public function testClientInvoiceFetch()
    {
        $account = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction = new ClientInvoice([
            "account_id" => $account->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);

        $transaction2 = new ClientInvoice([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now()->addWeeks(2),
            "narration" => $this->faker->word,
        ]);
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(ClientInvoice::fetch()), 2);
        $this->assertEquals(count(ClientInvoice::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(ClientInvoice::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(ClientInvoice::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(ClientInvoice::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $this->assertEquals(count(ClientInvoice::fetch(null, null, $account)), 1);
        $this->assertEquals(count(ClientInvoice::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(ClientInvoice::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(ClientInvoice::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(ClientInvoice::fetch(null, null, null, $currency)), 0);
    }
}
