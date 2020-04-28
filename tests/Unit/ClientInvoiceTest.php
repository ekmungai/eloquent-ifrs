<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\Ledger;

use Ekmungai\IFRS\Transactions\ClientInvoice;

use Ekmungai\IFRS\Exceptions\LineItemAccount;
use Ekmungai\IFRS\Exceptions\MainAccount;

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

        $clientInvoice = ClientInvoice::new($clientAccount, Carbon::now(), $this->faker->word);
        $clientInvoice->setDate(Carbon::now());
        $clientInvoice->save();

        $this->assertEquals($clientInvoice->getAccount()->name, $clientAccount->name);
        $this->assertEquals($clientInvoice->getAccount()->description, $clientAccount->description);
        $this->assertEquals($clientInvoice->getTransactionNo(), "IN0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting ClientInvoice Transaction
     *
     * @return void
     */
    public function testPostClientInvoiceTransaction()
    {
        $clientInvoice = ClientInvoice::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECEIVABLE,
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
                "account_type" => Account::OPERATING_REVENUE
            ])->id,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->post_account, $clientInvoice->getAccount()->id);
        $this->assertEquals($debit->folio_account, $lineItem->account_id);
        $this->assertEquals($credit->folio_account, $clientInvoice->getAccount()->id);
        $this->assertEquals($credit->post_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->post_account, $clientInvoice->getAccount()->id);
        $this->assertEquals($vat_debit->folio_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_credit->folio_account, $clientInvoice->getAccount()->id);
        $this->assertEquals($vat_credit->post_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($clientInvoice->getAmount(), 116);
    }

    /**
     * Test Client InvoiceLine Item Account.
     *
     * @return void
     */
    public function testClientInvoiceLineItemAccount()
    {
        $clientInvoice = ClientInvoice::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Client Invoice LineItem Account must be of type Operating Revenue');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
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
        $clientInvoice = ClientInvoice::new(
            factory('Ekmungai\IFRS\Models\Account')->create([
                'account_type' => Account::RECONCILIATION,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Client Invoice Main Account must be of type Receivable');

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory('Ekmungai\IFRS\Models\Vat')->create([
                "rate" => 16
            ])->id,
            "account_id" => factory('Ekmungai\IFRS\Models\Account')->create([
                "account_type" => Account::RECONCILIATION
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
        $transaction = ClientInvoice::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $found = ClientInvoice::find($transaction->getId());
        $this->assertEquals($found->getTransactionNo(), $transaction->getTransactionNo());
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
        $transaction = ClientInvoice::new(
            $account,
            Carbon::now(),
            $this->faker->word
        );
        $transaction->save();

        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
        ]);
        $transaction2 = ClientInvoice::new(
            $account2,
            Carbon::now()->addWeeks(2),
            $this->faker->word
        );
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
