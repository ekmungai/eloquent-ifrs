# Laravel IFRS
[![Build Status](https://travis-ci.com/ekmungai/laravel-ifrs.svg?branch=master)](https://travis-ci.com/ekmungai/laravel-ifrs)
![PHP 7.2](https://img.shields.io/badge/PHP-7.2-blue.svg)
Laravel Double Entry Accounting with a focus on IFRS Compliant Reporting.

With a fluent interface for creating Accounts, Transactions and Reports, this package transforms your Business Objects into Standard Accounting Reports such as an Income Statement (Profit and Loss) and a Balance Sheet.

The package supports multiple Entities (Companies), Account Categorization, Transaction assignment, Start of Year Opening Balances and accounting for VAT Transactions.

## Table of contents
1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Usage](#usage)
4. [Getting involved](#getting-involved)
5. [Contributing](#contributing)
6. [Roadmap](#roadmap)
7. [License](#license)
8. [References](#references)

## Installation

Use composer to Install the package into your laravel application. Laravel IFRS requires PHP versions 5.6 - 7.2.

#### For production

```
composer require "ekmungai/laravel-ifrs"
composer install --no-dev
```

Then run migrations to create the database tables.

#### For development

Clone this repo, and then run Composer in local repo root to pull in dependencies.

```
git clone git@github.com/ekmungai/laravel-ifrs laravel-ifrs
cd laravel-ifrs
composer update
```

To run the tests:

```
cd laravel-ifrs
vendor/bin/phpunit
```

## Configuration

The package installs with the default settings as regards the names of Accounts/Transactions Types, Report Titles and Section names as well as Accounts Codes. To adjust these settings use the Laravel artisan publish command to install the ifrs configuration to your applications config folder where you can edit it.

```
php artisan vendor:publish
```

## Usage

This simple example covers the four scenarios to demonstrate the use of the package. First, a description of a Cash Sale to a customer, then a Credit Sale (Invoice) to a client, then a Cash Purchase for an operations expense and finally a Credit Purchase (Bill) from a Supplier for a non operations purpose (Asset Purchase).

First we'll setup the Company (Reporting Entity) and required Accounts to record the Transactions. (Assuming that a registered User already exists):

```
use Ekmungai\IFRS\Models\Entity;
use Ekmungai\IFRS\Models\Currency;

$currency = Currency::new("Euro", "EUR"); //Entities require a reporting currency
$currency->save();

$entity = Entity::new("Example Company", $currency);
$entity->save();

```
We also need a VAT Rates that apply to the Entity:

```
use Ekmungai\IFRS\Models\Vat;

$outputVat = Vat::new(
    "Output VAT",           // VAT Name/Label
    "Standard Output Vat",  // VAT Description
    20                      // VAT Rate
);
$outputVat->save();

$inputVat = Vat::new(
    "Input VAT",
    "Standard Input Vat",
    10
);
$inputVat->save();

$zeroVat = Vat::new(
    "Zero VAT",
    "Vat for Zero Vat Transactions",
    0
);
$inputVat->save();
```

We need a bank account to receive payments from Clients and make payments to Suppliers:

```
use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Category;

$bankCategory = Category::new("Current Account", Account::BANK);
$bankCategory->save();

$bankAccount = Account::new(
    "Bank Account",                     // account name
    Account::BANK,                      // account type
    "Company operations Bank Account",  // account description
    $bankCategory,                      // account category
);
$bankAccount->save();
```

Sales made need to be accounted for:

```
$revenueCategory1 = Category::new("Cash Sales", Account::OPERATING_REVENUE);
$revenueCategory1->save();

$revenueAccount1 = Account::new(
    "Cash Sales Account",
    Account::OPERATING_REVENUE,
    "Account for recording Cash Sales",
    $revenueCategory1,
);
$revenueAccount1->save();

$revenueCategory2 = Category::new("Credit Sales", Account::OPERATING_REVENUE);
$revenueCategory2->save();

$revenueAccount2 = Account::new(
    "Credit Sales Account",
    Account::OPERATING_REVENUE,
    "Account for recording Credit Sales",
    $revenueCategory2,
);
$revenueAccount->save();

```

Sales made to Clients on credit need to be accounted for:

```
$clientCategory = Category::new("Local Clients", Account::RECEIVABLE);
$clientCategory->save();

$clientAccount = Account::new(
    "Example Client Account",
    Account::RECEIVABLE,
    "Example Client Account",
    $clientCategory,
);
$clientAccount->save();
```

Puchases from Suppliers made need to be accounted for:

```
$supplierCategory = Category::new("Local Suppliers", Account::PAYABLE);
$supplierCategory->save();

$supplierAccount = Account::new(
    "Example Supplier Account",
    Account::Account::PAYABLE,
    "Example Supplier Account",
    $supplierCategory,
);
$supplierAccount->save();
```

Operations Expenses made need to be accounted for:

```
$cosCategory = Category::new("Cash Expenses", Account::OPERATING_EXPENSE);
$cosCategory->save();

$cosAccount = Account::new(
    "Operations Expense Account",
    Account::OPERATING_EXPENSE,
    "Account for recording Cost of Goods/Services Sold",
    $cosCategory,
);
$cosAccount->save();
```

Asset Purchases made need to be accounted for:

```
$assetCategory = Category::new("Office Equipment", Account::NON_CURRENT_ASSET);
$assetCategory->save();

$assetAccount = Account::new(
    "Office Equipment Account",
    Account::NON_CURRENT_ASSET,
    "Account for recording Office Equipment Purchases and Disposals",
    $assetCategory,
);
$assetAccount->save();

```
Finally we need accounts for VAT:

```
$vatCategory1 = Category::new("Sales VAT", Account::CONTROL_ACCOUNT);
$vatCategory1->save();

$salesVatAccount = Account::new(
    "Sales VAT Account",
    Account::CONTROL_ACCOUNT,
    "Account for recording Vat from Sales",
    $vatCategory1,
);
$salesVatAccount->save();

$vatCategory2 = Category::new("Input VAT", Account::CONTROL_ACCOUNT);
$vatCategory2->save();

$purchasesVatAccount = Account::new(
    "Input VAT Account",
    Account::CONTROL_ACCOUNT,
    "Account for recording Vat from Purchases",
    $vatCategory2,
);
$purchasesVatAccount->save();

```

Now that all Accounts are prepared, we can create the first Transaction, a Cash Sale:

```
use Ekmungai\IFRS\Transactions\CashSale;

$cashSale = CashSale::new(
    $bankAccount,         // Main Account
    Carbon::now(),        // Transaction Date(time)
    "Example Cash Sale"   // Transaction Narration
);
$cashSale->save(); // Intermediate save does not record the transaction in the Ledger

```
So far the Transaction has only one side of the double entry, so we create a Line Item for the other side:

```
use Ekmungai\IFRS\models\LineItem;

$cashSaleLineItem = LineItem::new(
    $revenueAccount1,                   // Revenue Account
    $outputVat,                         // Output VAT
    100,                                // Item Price
    1,                                  // Quantity
    "Example Cash Sale Line Item",      // Item Narration
    $salesVatAccount                    // Vat Account
);
$cashSaleLineItem->save();

$cashSale->addLineItem($cashSaleLineItem);
$cashSale->post(); // This posts the Transaction to the Ledger

```
Client Invioces are almost identical to Cash Sales, only replacing the Bank Account with a Client Account:

```
use Ekmungai\IFRS\Transactions\ClientInvoice;

$clientInvoice = ClientInvoice::new(
    $clientAccount,
    Carbon::now(),
    "Example Credit Sale"
);

$clientInvoiceLineItem = LineItem::new(
    $revenueAccount2,                   // Revenue Account
    $outputVat,                         // Output VAT
    50,                                 // Item Price
    2,                                  // Quantity
    "Example Credit Sale Line Item",    // Item Narration
    $salesVatAccount                    // Vat Account
);

$clientInvoice->addLineItem($clientInvoiceLineItem); // Line Item save may be skipped as saving the Transaction saves the all its Line Items automatically
$clientInvoice->post(); // Transaction save may be skipped as post() saves the Transaction automatically

```
Then we have a Cash Purchase:

```
use Ekmungai\IFRS\Transactions\CashPurchase;

$cashPurchase = CashPurchase::new(
    $bankAccount,
    Carbon::now(),
    "Example Cash Purchase"
);

$cashPurchaseLineItem = LineItem::new(
    $cosAccount,                        // Cost of Sales Account
    $inputVat,                          // Input VAT
    25,                                 // Item Price
    4,                                  // Quantity
    "Example Cash Purchase Line Item",  // Item Narration
    $purchaseVatAccount                 // Vat Account
);

$cashPurchase->addLineItem($cashPurchaseLineItem);
$cashPurchase->post();

```
Asset Purchase on credit:

```
use Ekmungai\IFRS\Transactions\SupplierBill;

$supplierBill = SupplierBill::new(
    $supplierAccount,
    Carbon::now(),
    "Example Credit Purchase"
);

$supplierBillLineItem = LineItem::new(
    $assetAccount,                      // Asset Account
    $inputVat,                          // Input VAT
    25,                                 // Item Price
    4,                                  // Quantity
    "Example Credit Purchase Line Item",// Item Narration
    $purchaseVatAccount                 // Vat Account
);

$supplierBill->addLineItem($supplierBillLineItem);
$supplierBill->post();

```
Finally lets record a partial payment received from the client:

```
use Ekmungai\IFRS\Transactions\ClientReceipt;

$clientReceipt = ClientReceipt::new(
    $clientAccount,
    Carbon::now(),
    "Example Client Payment"
);

$clientReceiptLineItem = LineItem::new(
    $bankAccount,                       // Bank Account
    $zeroVat,                           // Zero VAT
    50,                                 // Amount Received
    1,                                  // Default Line Item Quantity
    "Part payment for Client Invoice",  // Item Narration
    null                                // Vat Account is not needed
);

$clientReceipt->addLineItem($clientReceiptLineItem);
$clientReceipt->post();

```
We can assign this receipt to partially clear the Invoice above:

```
use Ekmungai\IFRS\Models\Assignment;

echo $clientInvoice->clearedAmount(); //0: Currently the Invoice has not been cleared at all
echo $clientReceipt->balance(); //50: The Receipt has not been assigned to clear any transaction

$assignment = Assignment::new(
    $clientReceipt,   // Clearing Transaction
    $clientInvoice,   // Transaction being cleared
    50                // Amount to be cleared
);
$assignment->save();

echo $clientInvoice->clearedAmount(); //50
echo $clientReceipt->balance(); //0: The Receipt has been assigned fully to the Invoice

```
We have now some Transactions in the Ledger, so lets generate some reports. First though, Reports require a reporting period:

```
use Ekmungai\IFRS\Models\ReportingPeriod;

$period = ReportingPeriod::new(
    1,     // Reporting period count
    2020   // Calendar Year
);
$period->save();

```
We begin by generating the Income Statement (Profit and Loss):

```
use Ekmungai\IFRS\Reports\IncomeStatement;

$incomeStatement = new IncomeStatement(
    "2020-01-01",   // Report start date
    "2020-12-31",   // Report end date
);
$incomeStatement->getSections();// Fetch balances from the ledger and store them internally

```
At this point the Income Statement object contains an array of sections as defined in the configuration with total balances for each section. Printing the statement to a string yields:

```
/**
* this function is only for demonstration and
* debugging use and should never be called in production
*/
$incomeStatement->toString();

Example Company
Income Statement
For the Period: Jan 01 2020 to Dec 31 2020

Operating Revenues
    Operating Revenue        200 (100 cash sales + 100 credit sales)

Operating Expenses
    Operating Expense        100 (cash purchase)
                        ---------------
Operations Gross Profit      100

Non Operating Revenues
    Non Operating Revenue    0
                        ---------------
Total Revenue                100

Non Operating Expenses
    Direct Expense           0
    Overhead Expense         0
    Other Expense            0
                        ---------------
Total Expenses               0
                        ---------------
Net Profit                   100
                        ===============

```
Next we generate the Balance Sheet:

```
use Ekmungai\IFRS\Reports\BalanceSheet;

$balanceSheet = new BalanceSheet(
    "2020-12-31"  // Report end date
);
$balanceSheet->getSections();

/**
* again to emphasize, this function is only for demonstration and
* debugging use and should never be called in production
*/
$balanceSheet->toString();

Example Company
Balance Sheet
As at: Dec 31 2020

Assets
    Non Current Asset        120 (asset purchase)
    Receivables              70  (100 credit sale + 20 VAT - 50 client receipt)
    Bank                     50  (120 cash sale - 120 cash purchase + 50 client receipt)
                        ---------------
Total Assets                 240

Liabilities
    Control Account          20  (VAT: 20 cash sale + 20 credit sale - 10 cash purchase - 10 credit purchase)
    Payable                  120 (100 credit purchase + 20 VAT)
                        ---------------
Total Liabilities            140

                        ---------------
Net Assets                   100
                        ===============

Equity
    Income Statement         100
                        ---------------
Total Equity                 100
                        ===============

```
While the Income Statement and Balance Sheet are the ultimate goal for end year (IFRS) reporting, the package also provides intermediate period reports including Account Statement, which shows a chronological listing of all Transactions posted to an account ending with the current balance for the account; and Account Schedule, which is similar to an Account Statement with the difference that rather than list all Transactions that constitute the ending balance the report only shows the outstanding (Uncleared) Transactions.

In the above example:

```
use Ekmungai\IFRS\Reports\AccountStatement;
use Ekmungai\IFRS\Reports\AccountSchedule;

$statement = new AccountStatement($clientAccount);
$statement->getTransactions();

dd($statement->transactions);

array:2[
  ["transaction" => ClientInvoice, "debit" => 120, "credit" => 0, "balance" => 120],
  ["transaction" => ClientReceipt, "debit" => 0, "credit" => 50, "balance" => 70]
]

$schedule = new AccountSchedule($clientAccount, $currency);
$schedule->getTransactions();

dd($schedule->transactions);

array:1[
  ["transaction" => ClientInvoice, "amount" => 120, "cleared" => 50, "balance" => 70],
]

```
## Getting Involved

I am acutely aware that as a professionally trained Accountant I may have used some conventions, definitions and styles that while seemingly obvious to me, might not be so clear to another developer. I would therefore welcome and greatly appreciate any feedback on the ease of use of the package so I can make it more useful to as many people as possible.


## Contributing

1. Fork it (<https://github.com/ekmungai/laravel-ifrs/fork>)
2. Create your feature branch (`git checkout -b feature/fooBar`)
3. Write tests for the feature
3. Commit your changes (`git commit -am 'Add some fooBar'`)
4. Push to the branch (`git push origin feature/fooBar`)
5. Create a new Pull Request

## Roadmap

* Complete Documentation
* Add Multicurrency support
* Add Receivables(Debtors)/Payables(Creditors) Aging Balances analysis Report
* Add Cashflow Statement
* Add Changes in Equity Statement


## License
This software is distributed for free under the MIT License


## References
This package is heavily influenced by [simple-accounts-3](https://github.com/chippyash/simple-accounts-3) and [scottlaurent/accounting](https://github.com/scottlaurent/accounting).