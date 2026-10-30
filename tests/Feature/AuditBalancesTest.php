<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\Transaction;

use IFRS\Support\BalanceAudit;

use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\ClientReceipt;

class AuditBalancesTest extends TestCase
{
    /**
     * Create a Balance at the given rate.
     *
     * @param float $rate
     *
     * @return Balance
     */
    private function balance(float $rate): Balance
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
                'rate' => $rate,
                'currency_id' => $currency->id,
            ])->id,
            'reporting_period_id' => $this->period->id,
            'currency_id' => $currency->id,
            'transaction_date' => date('Y') . '-01-01',
            'balance' => 10,
        ]);
    }

    /**
     * Mark a Balance as saved after it was created.
     *
     * @param Balance $balance
     *
     * @return void
     */
    private function saveLater(Balance $balance): void
    {
        DB::table($balance->getTable())->where('id', $balance->id)->update([
            'created_at' => '2025-03-10 09:00:00',
            'updated_at' => '2025-04-10 09:00:00',
        ]);
    }

    /**
     * Assign a Client Receipt to a Client Invoice, leaving the invoice over cleared
     * in the way versions before 7.1.1 could.
     *
     * @param float $overBy
     *
     * @return Transaction
     */
    private function clearInvoice(float $overBy): Transaction
    {
        $currencyId = Auth::user()->entity->currency_id;

        $client = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currencyId,
        ]);

        $invoice = new ClientInvoice([
            'account_id' => $client->id,
            'narration' => $this->faker->word,
            'currency_id' => $currencyId,
        ]);
        $invoice->addLineItem(factory(LineItem::class)->create([
            'amount' => 100,
            'quantity' => 1,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => null,
            ])->id,
        ]));
        $invoice->post();

        $receipt = new ClientReceipt([
            'account_id' => $client->id,
            'narration' => $this->faker->word,
            'currency_id' => $currencyId,
        ]);
        $receipt->addLineItem(factory(LineItem::class)->create([
            'amount' => 200,
            'quantity' => 1,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currencyId,
            ])->id,
        ]));
        $receipt->post();

        $assignment = Assignment::create([
            'assignment_date' => now(),
            'transaction_id' => $receipt->id,
            'cleared_id' => $invoice->id,
            'cleared_type' => $invoice->cleared_type,
            'amount' => 100,
        ]);

        DB::table($assignment->getTable())->where('id', $assignment->id)->update(['amount' => 100 + $overBy]);

        return Transaction::find($invoice->id);
    }

    /**
     * Test a foreign currency Balance saved after it was created is a candidate.
     *
     * @return void
     */
    public function testCompoundedCandidates()
    {
        $balance = $this->balance(110);
        $this->saveLater($balance);

        $candidates = BalanceAudit::compoundedCandidates();

        $this->assertCount(1, $candidates);
        $this->assertEquals($balance->id, $candidates->first()->id);
    }

    /**
     * Test Balances the fault could not have touched are not candidates.
     *
     * @return void
     */
    public function testBalancesThatAreNotCandidates()
    {
        // Never saved again
        $this->balance(110);

        // A rate of 1 leaves the amount the same
        $this->saveLater($this->balance(1));

        $this->assertCount(0, BalanceAudit::compoundedCandidates());
    }

    /**
     * Test a Transaction cleared by more than its amount is found.
     *
     * @return void
     */
    public function testOverCleared()
    {
        $invoice = $this->clearInvoice(0.9);

        $findings = BalanceAudit::overCleared();

        $this->assertCount(1, $findings);
        $this->assertEquals($invoice->id, $findings->first()->id);
        $this->assertEquals(100, $findings->first()->amount);
        $this->assertEquals(100.9, $findings->first()->cleared);
    }

    /**
     * Test a Transaction cleared in full is not found.
     *
     * @return void
     */
    public function testFullyClearedIsNotFound()
    {
        $this->clearInvoice(0);

        $this->assertCount(0, BalanceAudit::overCleared());
    }

    /**
     * Test the command lists its findings and changes nothing.
     *
     * @return void
     */
    public function testCommandChangesNothing()
    {
        $balance = $this->balance(110);
        $this->saveLater($balance);
        $invoice = $this->clearInvoice(0.9);

        $this->artisan('ifrs:audit-balances')
            ->expectsOutputToContain('Nothing was changed')
            ->assertExitCode(0);

        $this->assertEquals(1100, $balance->fresh()->balance);
        $this->assertEquals(100.9, $invoice->cleared_amount);
    }

    /**
     * Test the command when there is nothing to report, for an Entity that does not
     * exist, and with nobody logged in.
     *
     * @return void
     */
    public function testCommandOptions()
    {
        $this->artisan('ifrs:audit-balances')
            ->expectsOutputToContain('Nothing to review')
            ->assertExitCode(0);

        $this->artisan('ifrs:audit-balances', ['--entity' => 999999])->assertExitCode(1);

        $balance = $this->balance(110);
        $this->saveLater($balance);

        Auth::logout();

        $this->artisan('ifrs:audit-balances')
            ->expectsOutputToContain('Nothing was changed')
            ->assertExitCode(0);
    }
}
