<?php

namespace Tests\Unit;

use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;
use IFRS\Models\Vat;
use IFRS\Tests\TestCase;
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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "account_id" => $lineAccount->id,
            "amount" => 50,
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

        $transaction = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "account_id" => $lineAccount1->id,
            "amount" => 75,
            "quantity" => 1,
        ]);

        $transaction->addLineItem($lineItem1);

        $lineItem2 = factory(LineItem::class)->create([
            "account_id" => $lineAccount2->id,
            "amount" => 120,
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
            "quantity" => 1,
        ]);
        $lineItem1->addVat(
            factory(Vat::class)->create(["rate" => 10])
        );
        $lineItem1->save();

        $transaction->addLineItem($lineItem1);

        $lineItem2 = factory(LineItem::class)->create([
            "account_id" => $lineAccount2->id,
            "amount" => 120,
            "quantity" => 1,
        ]);
        $lineItem2->addVat(
            factory(Vat::class)->create(["rate" => 10])
        );
        $lineItem2->save();

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

    /**
     * Test Ledger Model Account Contribution.
     *
     * @return void
     */
    public function testLedgerMultupleForeignCurrencies()
    {

        $line1Amount = 75;
        $line2Amount = 120.2;

        $line1Vat = 10.0;
        $line2Vat = 10.0;

        $transaction1ExchangeRate = 10.0;
        $transaction2ExchangeRate = 100.0;


        $transaction1Currency = factory(Currency::class)->create([
            'name' => 'Currency1',
            'currency_code' => 'CUR1',
        ]);

        $transaction2Currency = factory(Currency::class)->create([
            'name' => 'Currency2',
            'currency_code' => 'CUR2',
        ]);

        $transaction1ExchangeRateEntity = factory(ExchangeRate::class)->create([
            'valid_from' => Carbon::now()->startOfYear(),
            'currency_id' => $transaction1Currency->id,
            'rate' => $transaction1ExchangeRate,
        ]);

        $transaction2ExchangeRateEntity = factory(ExchangeRate::class)->create([
            'valid_from' => Carbon::now()->startOfYear(),
            'currency_id' => $transaction2Currency->id,
            'rate' => $transaction2ExchangeRate,
        ]);

        $account = factory(Account::class)->create([
            'category_id' => null,
//            'currency_id' => $transaction1Currency->id,
        ]);

        $lineAccount1 = factory(Account::class)->create([
            'category_id' => null,
            'currency_id' => $transaction2Currency->id,
        ]);

        $lineAccount2 = factory(Account::class)->create([
            'category_id' => null,
            'currency_id' => $transaction1Currency->id,
        ]);

        $transaction1 = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => $transaction1ExchangeRateEntity->id
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "account_id" => $lineAccount1->id,
            "amount" => $line1Amount,
            "quantity" => 1,
        ]);
        $lineItem1->addVat(
            factory(Vat::class)->create(["rate" => $line1Vat])
        );
        $lineItem1->save();

        $transaction1->addLineItem($lineItem1);

        $lineItem2 = factory(LineItem::class)->create([
            "account_id" => $lineAccount2->id,
            "amount" => $line2Amount,
            "quantity" => 1,
        ]);
        $lineItem2->addVat(
            factory(Vat::class)->create(["rate" => $line2Vat])
        );
        $lineItem2->save();

        $transaction1->addLineItem($lineItem2);

        $transaction1->post();

        $this->assertEquals(round(($line1Amount * (1 + $line1Vat / 100) + ($line2Amount * (1 + $line2Vat / 100))), 2), $transaction1->amount);
        $this->assertEquals($line1Amount * $transaction1ExchangeRate, Ledger::contribution($lineAccount1, $transaction1->id));
        $this->assertEquals($line2Amount * $transaction1ExchangeRate, Ledger::contribution($lineAccount2, $transaction1->id));
        $this->assertEquals($line1Amount, Ledger::contribution($lineAccount1, $transaction1->id, $transaction2ExchangeRateEntity->currency_id));
        $this->assertEquals($line2Amount, Ledger::contribution($lineAccount2, $transaction1->id, $transaction2ExchangeRateEntity->currency_id));

        $reportingCurrencyTransaction1Amount = round(($line1Amount * (1 + $line1Vat / 100) + ($line2Amount * (1 + $line2Vat / 100))) * $transaction1ExchangeRate * -1, 2);
        $eurTransaction1Amount = round(($line1Amount * (1 + $line1Vat / 100) + ($line2Amount * (1 + $line2Vat / 100))) * -1, 2);
        $this->assertEquals(
            [
                $this->reportingCurrencyId => $reportingCurrencyTransaction1Amount
            ],
            $account->currentBalance());
        $this->assertEquals(
            [
                $this->reportingCurrencyId => $reportingCurrencyTransaction1Amount,
                $transaction1ExchangeRateEntity->currency_id => $eurTransaction1Amount
            ],
            $account->currentBalance(null, null, $transaction1ExchangeRateEntity->currency_id)
        );


        $this->assertEquals(
            [
                $this->reportingCurrencyId => $line2Amount * $transaction1ExchangeRateEntity->rate,
                $transaction1ExchangeRateEntity->currency_id => $line2Amount
            ],
            $lineAccount2->currentBalance(null, null, $transaction1ExchangeRateEntity->currency_id)
        );

        // ----------------------
        $transaction2 = new JournalEntry([
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => $transaction2ExchangeRateEntity->id
        ]);

        $lineItem1 = factory(LineItem::class)->create([
            "account_id" => $lineAccount1->id,
            "amount" => $line1Amount,
            "quantity" => 1,
        ]);

        $transaction2->addLineItem($lineItem1);

        $transaction2->post();

        $this->assertEquals(
            [
                $this->reportingCurrencyId => $reportingCurrencyTransaction1Amount + round($line1Amount * $transaction2ExchangeRateEntity->rate * -1, 2)
            ]
            , $account->currentBalance());

        $this->assertEquals(
            [
                $this->reportingCurrencyId => -$line1Amount * $transaction2ExchangeRateEntity->rate,
                $transaction2ExchangeRateEntity->currency_id => -$line1Amount
            ],
            $account->currentBalance(null, null, $transaction2ExchangeRateEntity->currency_id)
        );
    }
}
