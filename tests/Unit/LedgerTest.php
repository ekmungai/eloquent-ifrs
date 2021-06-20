<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;


use Carbon\Carbon;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
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
        $account = factory(Account::class)->create([
            'category_id' => null
        ]);
        $lineAccount = factory(Account::class)->create([
            'category_id' => null
        ]);
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
     * Test Ledger Model Account Contribution.
     *
     * @return void
     */
    public function testLedgerAccountContribution()
    {
        $account = factory(Account::class)->create([
            'category_id' => null
        ]);
        $lineAccount1 = factory(Account::class)->create([
            'category_id' => null
        ]);
        $lineAccount2 = factory(Account::class)->create([
            'category_id' => null
        ]);
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
        $account = factory(Account::class)->create([
            'category_id' => null,
            "account_type" => Account::INVENTORY
        ]);

        factory(Ledger::class, 3)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::DEBIT,
            "posting_date" => Carbon::now(),
            "amount" => 50
        ]);

        factory(Ledger::class, 2)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::CREDIT,
            "posting_date" => Carbon::now(),
            "amount" => 95
        ]);
        
        $localBalance = Ledger::balance($account, Carbon::now()->startOfYear(), Carbon::now());
        $this->assertEquals($localBalance[$this->reportingCurrencyId], -40);
    }

    /**
     * Test Ledger Model Account Contribution.
     *
     * @return void
     */
    public function testLedgerForeignCurrency()
    {
        $account = factory(Account::class)->create([
            'category_id' => null
        ]);

        $lineAccount1 = factory(Account::class)->create([
            'category_id' => null
        ]);

        $lineAccount2 = factory(Account::class)->create([
            'category_id' => null
        ]);

        $vat = factory(Vat::class)->create(["rate" => 10]);

        $rate = factory(ExchangeRate::class)->create([
            'rate' => 105
        ]);
        
        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => $rate->id
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

        $this->assertEquals($transaction->amount, 214.50);
        $this->assertEquals(Ledger::contribution($lineAccount1, $transaction->id), 7875);
        $this->assertEquals(Ledger::contribution($lineAccount2, $transaction->id), 12600);
        $this->assertEquals(Ledger::contribution($lineAccount1, $transaction->id, $rate->currency_id), 75);
        $this->assertEquals(Ledger::contribution($lineAccount2, $transaction->id, $rate->currency_id), 120);

        $this->assertEquals($account->currentBalance(), [$this->reportingCurrencyId => -22522.50]);
        $this->assertEquals(
            $account->currentBalance(null, null, $rate->currency_id),
            [$this->reportingCurrencyId => -22522.50, $rate->currency_id => -214.50] 
        );

        $rate1 = factory(ExchangeRate::class)->create([
            'rate' => 10,
            'currency_id' => factory(Currency::class)->create()->id
        ]);
        
        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => $rate1->id
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "account_id" => $lineAccount1->id,
            "amount" => 75,
            "vat_id" => factory(Vat::class)->create(["rate" => 0])->id,
            "quantity" => 1,
        ]);

        $transaction->addLineItem($lineItem1);

        $transaction->post();

        $this->assertEquals($account->currentBalance(), [$this->reportingCurrencyId => -23272.50]);
        $this->assertEquals(
            $account->currentBalance(null, null, $rate1->currency_id),
            [$this->reportingCurrencyId => -750, $rate1->currency_id => -75] 
        );
    }
}
