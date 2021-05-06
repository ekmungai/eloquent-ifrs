<?php

namespace Tests\Feature;

use Carbon\Carbon;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Tests\TestCase;
use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\CreditNote;
use IFRS\Models\Assignment;
use IFRS\Models\Vat;
use IFRS\Transactions\JournalEntry;
use IFRS\Reports\AgingSchedule;

class AgingScheduleTest extends TestCase
{
    /**
     * Test Receivables AgingSchedule
     *
     * @return void
     */
    public function testReceivablesAgingSchedule()
    {
        $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);
        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);

        # 365+ transaction
        factory(Balance::class)->create([
            "account_id" => $account1->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "balance" => 25,
            'reporting_period_id' => $this->period->id,
        ]);

        # current transaction
        $date = Carbon::now()->endOfYear()->sub('days', 20);
        $clientInvoice = new ClientInvoice([
            "account_id" => $account1->id,
            "transaction_date" => $date,
            "narration" => $this->faker->word,
        ]);

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        //Partially clear Transaction
        $creditNote = new CreditNote([
            "account_id" => $account1->id,
            "transaction_date" => $date,
            "narration" => $this->faker->word,
        ]);

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 50,
        ]);
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        factory(Assignment::class)->create([
            'transaction_id' => $creditNote->id,
            'cleared_id' => $clientInvoice->id,
            "amount" => 50,
        ]);

        //Journal Entry Transaction (91 - 180 days)
        $journalEntry = new JournalEntry([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now()->endOfYear()->sub('months', 5),
            "narration" => $this->faker->word,
            "credited" => false,
        ]);

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 75,
        ]);
        $journalEntry->addLineItem($lineItem);

        $journalEntry->post();

        # 31 - 90 days transaction
        $clientInvoice = new ClientInvoice([
            "account_id" => $account2->id,
            "narration" => $this->faker->word,
            'transaction_date' => Carbon::now()->endOfYear()->sub('months', 2),
        ]);

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        # 181 - 270 days transaction
        $clientInvoice = new ClientInvoice([
            "account_id" => $account2->id,
            "narration" => $this->faker->word,
            'transaction_date' => Carbon::now()->endOfYear()->sub('months', 8),
        ]);

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 150,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        # 271 - 365 days transaction
        $clientInvoice = new ClientInvoice([
            "account_id" => $account2->id,
            "narration" => $this->faker->word,
            'transaction_date' => Carbon::now()->endOfYear()->sub('months', 10),
        ]);

        $lineItem = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 175,
        ]);
        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();


        $schedule = new AgingSchedule(Account::RECEIVABLE, Carbon::now()->endOfYear()->toDateString());

        $this->assertEquals($schedule->brackets, config('ifrs')['aging_schedule_brackets']);
        $this->assertEquals($schedule->balances['current'], 50);
        $this->assertEquals($schedule->balances['31 - 90 days'], 100);
        $this->assertEquals($schedule->balances['91 - 180 days'], 75);
        $this->assertEquals($schedule->balances['181 - 270 days'], 150);
        $this->assertEquals($schedule->balances['271 - 365 days'], 175);
        $this->assertEquals($schedule->balances['365+ (bad debts)'], 25);


        $this->assertEquals($schedule->accounts[0]->balances['current'], 50);
        $this->assertEquals($schedule->accounts[0]->balances['31 - 90 days'], 0);
        $this->assertEquals($schedule->accounts[0]->balances['91 - 180 days'], 75);
        $this->assertEquals($schedule->accounts[0]->balances['181 - 270 days'], 0);
        $this->assertEquals($schedule->accounts[0]->balances['271 - 365 days'], 0);
        $this->assertEquals($schedule->accounts[0]->balances['365+ (bad debts)'], 25);


        $this->assertEquals($schedule->accounts[1]->balances['current'], 0);
        $this->assertEquals($schedule->accounts[1]->balances['31 - 90 days'], 100);
        $this->assertEquals($schedule->accounts[1]->balances['91 - 180 days'], 0);
        $this->assertEquals($schedule->accounts[1]->balances['181 - 270 days'], 150);
        $this->assertEquals($schedule->accounts[1]->balances['271 - 365 days'], 175);
        $this->assertEquals($schedule->accounts[1]->balances['365+ (bad debts)'], 0);
    }
}
