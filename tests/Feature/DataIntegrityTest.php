<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Transaction;

use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\ClientReceipt;
use IFRS\Transactions\JournalEntry;

use IFRS\Exceptions\OverClearance;

class DataIntegrityTest extends TestCase
{
    /**
     * Create an Account in the reporting currency.
     *
     * @param string $type
     *
     * @return Account
     */
    private function account(string $type): Account
    {
        return factory(Account::class)->create([
            'account_type' => $type,
            'category_id' => null,
            'currency_id' => Auth::user()->entity->currency_id,
        ]);
    }

    /**
     * Create a foreign currency Balance of 10 at a rate of 110.
     *
     * @return Balance
     */
    private function foreignBalance(): Balance
    {
        $currency = factory(Currency::class)->create();

        return factory(Balance::class)->create([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            'balance_type' => Balance::DEBIT,
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'rate' => 110,
                'currency_id' => $currency->id,
            ])->id,
            'reporting_period_id' => $this->period->id,
            'currency_id' => $currency->id,
            'transaction_date' => date('Y') . '-01-01',
            'balance' => 10,
        ]);
    }

    /**
     * Post a Client Invoice and a Client Receipt for the given amounts.
     *
     * @param float $invoiced
     * @param float $received
     *
     * @return array
     */
    private function invoiceAndReceipt(float $invoiced, float $received): array
    {
        $client = $this->account(Account::RECEIVABLE);

        $invoice = new ClientInvoice([
            'account_id' => $client->id,
            'narration' => $this->faker->word,
            'currency_id' => $client->currency_id,
        ]);
        $invoice->addLineItem(factory(LineItem::class)->create([
            'amount' => $invoiced,
            'quantity' => 1,
            'account_id' => $this->account(Account::OPERATING_REVENUE)->id,
        ]));
        $invoice->post();

        $receipt = new ClientReceipt([
            'account_id' => $client->id,
            'narration' => $this->faker->word,
            'currency_id' => $client->currency_id,
        ]);
        $receipt->addLineItem(factory(LineItem::class)->create([
            'amount' => $received,
            'quantity' => 1,
            'account_id' => $this->account(Account::BANK)->id,
        ]));
        $receipt->post();

        return [$invoice, $receipt];
    }

    /**
     * Test saving a Balance again does not translate it again.
     *
     * @return void
     */
    public function testBalanceIsTranslatedOnce()
    {
        $balance = $this->foreignBalance();

        $this->assertEquals(1100, $balance->balance);

        $balance->reference = 'Edited';
        $balance->save();

        $this->assertEquals(1100, $balance->fresh()->balance);
        $this->assertEquals(10, $balance->fresh()->amount);
    }

    /**
     * Test restoring a Balance does not translate it again.
     *
     * @return void
     */
    public function testRestoredBalanceIsNotTranslated()
    {
        $balance = $this->foreignBalance();

        $balance->delete();
        $balance->restore();

        $this->assertEquals(1100, $balance->fresh()->balance);
    }

    /**
     * Test a new amount entered on a Balance is translated.
     *
     * @return void
     */
    public function testChangedBalanceIsTranslated()
    {
        $balance = $this->foreignBalance();

        $balance->balance = 20;
        $balance->save();

        $this->assertEquals(2200, $balance->fresh()->balance);
    }

    /**
     * Test a Transaction cannot be over cleared by less than 1.
     *
     * @return void
     */
    public function testFractionalOverClearance()
    {
        [$invoice, $receipt] = $this->invoiceAndReceipt(100, 200);

        $this->expectException(OverClearance::class);

        Assignment::create([
            'assignment_date' => now(),
            'transaction_id' => $receipt->id,
            'cleared_id' => $invoice->id,
            'cleared_type' => $invoice->cleared_type,
            'amount' => 100.9,
        ]);
    }

    /**
     * Test a Transaction can be cleared in full, including by a fractional amount.
     *
     * @return void
     */
    public function testFractionalClearance()
    {
        [$invoice, $receipt] = $this->invoiceAndReceipt(100.5, 200);

        Assignment::create([
            'assignment_date' => now(),
            'transaction_id' => $receipt->id,
            'cleared_id' => $invoice->id,
            'cleared_type' => $invoice->cleared_type,
            'amount' => 100.5,
        ]);

        $this->assertEquals(100.5, Transaction::find($invoice->id)->cleared_amount);
    }

    /**
     * Test the Transaction Currencies of a Reporting Period are those of its Entity.
     *
     * @return void
     */
    public function testTransactionCurrenciesOfOtherEntity()
    {
        $user = Auth::user();
        $other = factory(User::class)->create();

        $this->be($other);

        $entity = $other->entity;
        $entity->currency_id = factory(Currency::class)->create()->id;
        $entity->save();

        factory(ReportingPeriod::class)->create([
            'calendar_year' => date('Y'),
            'entity_id' => $entity->id,
        ]);

        $this->foreignJournal();

        $this->be($user);

        $this->assertCount(0, $this->period->transactionCurrencies());

        $this->period->status = ReportingPeriod::ADJUSTING;

        $this->assertEquals([], $this->period->prepareBalancesTranslation(
            $this->account(Account::EQUITY)->id
        ));
    }

    /**
     * Test a deleted Transaction is not among the Transaction Currencies.
     *
     * @return void
     */
    public function testTransactionCurrenciesWithDeletedTransaction()
    {
        $journal = $this->foreignJournal();

        $this->assertCount(1, $this->period->transactionCurrencies());

        $journal->delete();

        $this->assertCount(0, $this->period->transactionCurrencies());
    }

    /**
     * Save a foreign currency Journal Entry.
     *
     * @return JournalEntry
     */
    private function foreignJournal(): JournalEntry
    {
        $currency = factory(Currency::class)->create();

        $journal = new JournalEntry([
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            'narration' => $this->faker->word,
            'currency_id' => $currency->id,
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'rate' => 2,
                'currency_id' => $currency->id,
            ])->id,
        ]);
        $journal->save();

        return $journal;
    }
}
