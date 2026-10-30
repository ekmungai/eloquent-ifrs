<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\LineItem;
use IFRS\Models\Vat;

use IFRS\Transactions\CashPurchase;
use IFRS\Transactions\CashSale;
use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\ClientReceipt;
use IFRS\Transactions\JournalEntry;

/**
 * The worked examples of the documentation, as published, with their printed
 * balances as assertions. When an example changes here, change the page too.
 */
class DocumentationExamplesTest extends TestCase
{
    /**
     * Create the Sales VAT objects of the sales cycle page.
     *
     * @return array
     */
    private function salesVat(): array
    {
        $salesVatAccount = Account::create([
            'name' => "Sales VAT Account",
            'account_type' => Account::CONTROL,
        ]);

        $outputVat = Vat::create([
            'name' => "Standard Output Vat",
            'code' => "S",
            'account_id' => $salesVatAccount->id,
            'rate' => 20,
        ]);

        return [$salesVatAccount, $outputVat];
    }

    /**
     * Sales Cycle: Cash Sale and Discount.
     *
     * @return void
     */
    public function testCashSaleWithDiscount()
    {
        $bankAccount = Account::create([
            'name' => "Bank Account",
            'account_type' => Account::BANK,
        ]);

        $revenueAccount = Account::create([
            'name' => "Sales Account",
            'account_type' => Account::OPERATING_REVENUE,
        ]);

        $cashSale = CashSale::create([
            'account_id' => $bankAccount->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Example Cash Sale",
        ]);

        [$salesVatAccount, $outputVat] = $this->salesVat();

        $cashSaleLineItem = LineItem::create([
            'account_id' => $revenueAccount->id,
            'narration' => "Example Cash Sale Line Item",
            'amount' => 100,
        ]);

        $cashSaleLineItem->addVat($outputVat);
        $cashSaleLineItem->save();

        $cashSale->addLineItem($cashSaleLineItem);
        $cashSale->post();

        $discountsAllowedAccount = Account::create([
            'name' => "Discounts Allowed Account",
            'account_type' => Account::OTHER_EXPENSE,
        ]);

        $journalEntry = JournalEntry::create([
            'account_id' => $bankAccount->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Example Discount Allowed entry",
        ]);

        $journalEntry->addLineItem(
            LineItem::create([
                'account_id' => $discountsAllowedAccount->id,
                'narration' => "Example Discount Allowed entry",
                'amount' => 10,
            ])
        );

        $journalEntry->post();

        $currencyId = Auth::user()->entity->currency_id;

        $this->assertEquals(110, $bankAccount->closingBalance()[$currencyId]);
        $this->assertEquals(-100, $revenueAccount->closingBalance()[$currencyId]);
        $this->assertEquals(-20, $salesVatAccount->closingBalance()[$currencyId]);
        $this->assertEquals(10, $discountsAllowedAccount->closingBalance()[$currencyId]);
    }

    /**
     * Sales Cycle: Credit Sale with a VAT inclusive amount, and its receipt.
     *
     * @return void
     */
    public function testCreditSale()
    {
        $bankAccount = Account::create([
            'name' => "Bank Account",
            'account_type' => Account::BANK,
        ]);

        $revenueAccount = Account::create([
            'name' => "Sales Account",
            'account_type' => Account::OPERATING_REVENUE,
        ]);

        [$salesVatAccount, $outputVat] = $this->salesVat();

        $clientAccount = Account::create([
            'name' => "Example Client Account",
            'account_type' => Account::RECEIVABLE,
        ]);

        $clientInvoice = ClientInvoice::create([
            'account_id' => $clientAccount->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Example Credit Sale",
        ]);

        $creditSaleLineItem = LineItem::create([
            'account_id' => $revenueAccount->id,
            'narration' => "Example Credit Sale Line Item",
            'vat_inclusive' => true,
            'amount' => 100,
        ]);

        $creditSaleLineItem->addVat($outputVat);
        $creditSaleLineItem->save();

        $clientInvoice->addLineItem($creditSaleLineItem);
        $clientInvoice->post();

        $currencyId = Auth::user()->entity->currency_id;

        $this->assertEquals(100, $clientAccount->closingBalance()[$currencyId]);
        $this->assertEquals(-83.3333, round($revenueAccount->closingBalance()[$currencyId], 4));
        $this->assertEquals(-16.6667, round($salesVatAccount->closingBalance()[$currencyId], 4));

        $clientReceipt = ClientReceipt::create([
            'account_id' => $clientAccount->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Example Client Payment",
        ]);

        $clientReceipt->addLineItem(
            LineItem::create([
                'account_id' => $bankAccount->id,
                'narration' => "Payment for Client Invoice",
                'amount' => 100,
            ])
        );

        $clientReceipt->post();

        $this->assertEquals(0, $clientAccount->closingBalance()[$currencyId]);
        $this->assertEquals(-83.3333, round($revenueAccount->closingBalance()[$currencyId], 4));
        $this->assertEquals(-16.6667, round($salesVatAccount->closingBalance()[$currencyId], 4));
        $this->assertEquals(100, $bankAccount->closingBalance()[$currencyId]);
    }

    /**
     * Purchase Cycle: Cash Purchase and Refund.
     *
     * @return void
     */
    public function testCashPurchaseWithRefund()
    {
        $bankAccount = Account::create([
            'name' => "Bank Account",
            'account_type' => Account::BANK,
        ]);

        $cashPurchase = CashPurchase::create([
            'account_id' => $bankAccount->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Example Cash Purchase",
        ]);

        $opexAccount = Account::create([
            'name' => "Operations Expense Account",
            'account_type' => Account::OPERATING_EXPENSE,
        ]);

        $inputVatAccount = Account::create([
            'name' => "Input VAT Account",
            'account_type' => Account::CONTROL,
        ]);

        $inputVat = Vat::create([
            'name' => "Standard Input Vat",
            'code' => "I",
            'account_id' => $inputVatAccount->id,
            'rate' => 10,
        ]);

        $cashPurchaseLineItem = LineItem::create([
            'account_id' => $opexAccount->id,
            'narration' => "Example Cash Purchase Line Item",
            'amount' => 100,
        ]);

        $cashPurchaseLineItem->addVat($inputVat);
        $cashPurchaseLineItem->save();

        $cashPurchase->addLineItem($cashPurchaseLineItem);
        $cashPurchase->post();

        $journalEntry = JournalEntry::create([
            'account_id' => $bankAccount->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Example refund for goods returned",
            'credited' => false, // the refund is money coming into the bank
        ]);

        $refundLineItem = LineItem::create([
            'account_id' => $opexAccount->id,
            'narration' => "Example refund for goods returned",
            'amount' => 50,
        ]);

        $refundLineItem->addVat($inputVat);
        $refundLineItem->save();

        $journalEntry->addLineItem($refundLineItem);
        $journalEntry->post();

        $currencyId = Auth::user()->entity->currency_id;

        $this->assertEquals(-55, $bankAccount->closingBalance()[$currencyId]);
        $this->assertEquals(50, $opexAccount->closingBalance()[$currencyId]);
        $this->assertEquals(5, $inputVatAccount->closingBalance()[$currencyId]);
    }

    /**
     * Create the accounts of the compound journals page.
     *
     * @return array
     */
    private function journalAccounts(): array
    {
        $accounts = [];

        foreach (["Main Account", "Line Account 1", "Line Account 2", "Line Account 3"] as $name) {
            $accounts[] = Account::create([
                'name' => $name,
                'account_type' => Account::BANK,
            ]);
        }

        return $accounts;
    }

    /**
     * Compound Journals: Basic Journal Entry.
     *
     * @return void
     */
    public function testBasicJournalEntry()
    {
        [$account1, $account2, $account3, $account4] = $this->journalAccounts();

        $journalEntry = JournalEntry::create([
            'account_id' => $account1->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Basic Journal Entry",
        ]);

        foreach ([$account2, $account3, $account4] as $index => $account) {
            $journalEntry->addLineItem(
                LineItem::create([
                    'account_id' => $account->id,
                    'narration' => "Line Item " . ($index + 1),
                    'amount' => 100,
                ])
            );
        }

        $journalEntry->post();

        $currencyId = Auth::user()->entity->currency_id;

        $this->assertEquals(-300, $account1->closingBalance()[$currencyId]);
        $this->assertEquals(100, $account2->closingBalance()[$currencyId]);
        $this->assertEquals(100, $account3->closingBalance()[$currencyId]);
        $this->assertEquals(100, $account4->closingBalance()[$currencyId]);
    }

    /**
     * Compound Journals: Compound Journal Entry.
     *
     * @return void
     */
    public function testCompoundJournalEntry()
    {
        [$account1, $account2, $account3, $account4] = $this->journalAccounts();

        $journalEntry = JournalEntry::create([
            'account_id' => $account1->id,
            'transaction_date' => Carbon::now(),
            'narration' => "Compound Journal Entry",
            'credited' => false, // main account should be debited
            'compound' => true,
            'main_account_amount' => 10
        ]);

        $journalEntry->addLineItem(
            LineItem::create([
                'account_id' => $account2->id,
                'narration' => "Line Item 1",
                'amount' => 25,
                'credited' => true
            ])
        );

        $journalEntry->addLineItem(
            LineItem::create([
                'account_id' => $account3->id,
                'narration' => "Line Item 2",
                'amount' => 30,
                'credited' => false
            ])
        );

        $journalEntry->addLineItem(
            LineItem::create([
                'account_id' => $account4->id,
                'narration' => "Line Item 3",
                'amount' => 15,
                'credited' => true
            ])
        );

        $journalEntry->post();

        $currencyId = Auth::user()->entity->currency_id;

        $this->assertEquals(10, $account1->closingBalance()[$currencyId]);
        $this->assertEquals(-25, $account2->closingBalance()[$currencyId]);
        $this->assertEquals(30, $account3->closingBalance()[$currencyId]);
        $this->assertEquals(-15, $account4->closingBalance()[$currencyId]);
    }

    /**
     * Transactions: a date key is ignored, and the transaction is dated now.
     *
     * @return void
     */
    public function testDateKeyIsIgnored()
    {
        $account = Account::create([
            'name' => "Bank Account",
            'account_type' => Account::BANK,
        ]);

        $journalEntry = JournalEntry::create([
            'account_id' => $account->id,
            'date' => '2020-05-05',
            'narration' => "Example",
        ]);

        $this->assertEquals(date('Y-m-d'), Carbon::parse($journalEntry->transaction_date)->toDateString());
    }
}
