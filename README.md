# Eloquent IFRS

[![Build Status](https://app.travis-ci.com/ekmungai/eloquent-ifrs.svg?branch=master)](https://travis-ci.com/ekmungai/eloquent-ifrs)
[![Test Coverage](https://api.codeclimate.com/v1/badges/7afac1253d0f662d1cfd/test_coverage)](https://codeclimate.com/github/ekmungai/eloquent-ifrs/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/7afac1253d0f662d1cfd/maintainability)](https://codeclimate.com/github/ekmungai/eloquent-ifrs/maintainability)
![PHP 8.0](https://img.shields.io/badge/PHP-8.0-blue.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Total Downloads](https://poser.pugx.org/ekmungai/eloquent-ifrs/downloads)](//packagist.org/packages/ekmungai/eloquent-ifrs)
[![Latest Stable Version](https://poser.pugx.org/ekmungai/eloquent-ifrs/v)](//packagist.org/packages/ekmungai/eloquent-ifrs)

This Package enables any Laravel application to generate [International Financial Reporting Standards](https://www.ifrs.org/issued-standards/list-of-standards/conceptual-framework/) compatible Financial Statements by providing a fully featured and configurable Double Entry accounting subsystem.

The package supports multiple Entities (Companies), Account Categorization, Transaction assignment, Start of Year Opening Balances and accounting for VAT Transactions. Transactions are also protected against tampering via direct database changes ensuring the integrity of the Ledger. Outstanding amounts for clients and suppliers can also be displayed according to how long they have been outstanding using configurable time periods (Current, 31 - 60 days, 61 - 90 days etc). Finally, the package supports the automated posting of forex difference transactions both within the reporting period as well as translating foreign denominated account balances at a set closing rate.

The motivation for this package can be found in detail on my blog post [here](https://karanjamungai.com/posts/accounting_software/)
## Table of contents
- [Eloquent IFRS](#eloquent-ifrs)
  - [Table of contents](#table-of-contents)
  - [Installation](#installation)
      - [For production](#for-production)
      - [For development](#for-development)
  - [Configuration](#configuration)
  - [Usage](#usage)
    - [DB Collision](#db-collision)
    - [Examples](#examples)
  - [Changelog](#changelog)
  - [Getting Involved](#getting-involved)
  - [Contributing](#contributing)
  - [Roadmap](#roadmap)
  - [License](#license)
  - [References](#references)

## Installation

Use composer to Install the package into your laravel or lumen application. Eloquent IFRS requires PHP version 8.0.2 and Eloquent version 8 and above.

#### For production

```php
composer require "ekmungai/eloquent-ifrs"
```

If using Lumen, make sure to register the package with your application by adding the `IFRSServiceProvider` to the `app.php` in the bootstrap folder.

```php
<?php

use IFRS\IFRSServiceProvider;

require_once __DIR__.'/../vendor/autoload.php';
...

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
$app->register(IFRSServiceProvider::class);
  ...
}
...
?>
```

Then run migrations to create the database tables.

```php
php artisan migrate
```

#### For development

Clone this repo, and then run Composer in local repo root to pull in dependencies.

```php
git clone git@github.com/ekmungai/eloquent-ifrs eloquent-ifrs
cd eloquent-ifrs
composer update
```

To run the tests:

```php
cd eloquent-ifrs
vendor/bin/phpunit
```

## Configuration

The package installs with the default settings as regards the names of Accounts/Transactions Types, Report Titles and Section names as well as Accounts Codes. To adjust these settings use the Laravel artisan publish command to install the ifrs configuration to your application's config folder where you can edit it.

```php
php artisan vendor:publish
```

## Usage
Full documentation for this package can be found [here](https://ekmungai.github.io/ifrs-docs/).

### DB Collision
Publish configuration file with `vendor:publish` if your `User` model is different from `App\User` and update the namespace of your `User` model.

Open your `User` model and implement the below interfaces and also include the trait as well.

```php
<?php

use IFRS\Traits\IFRSUser;
use IFRS\Traits\Recycling;

use IFRS\Interfaces\Recyclable;
...

class User ... implements Recyclable {
  ...
  use IFRSUser;
  use Recycling;
  ...
}
...
?>
```

### Examples
This simple example covers the four scenarios to demonstrate the use of the package. First, a description of a Cash Sale to a customer, then a Credit Sale (Invoice) to a client, then a Cash Purchase for an operations expense and finally a Credit Purchase (Bill) from a Supplier for a non operations purpose (Asset Purchase).

First we'll setup the Company (Reporting Entity) and required Accounts to record the Transactions. (Assuming that a registered User already exists):

```php
use IFRS\Models\Entity;
use IFRS\Models\Currency;

$entity = Entity::create([
    "name" => "Example Company",
]);

//Entities require a reporting currency
$currency = Currency::create([
    "name" => "Euro",
    "currency_code" => "EUR"
]);

// Set the currency as the Entity's Reporting Currency 
$entity->currency_id = $currency->id;
$entity->save();
```
We also need the VAT Rates that apply to the Entity:

```php
use IFRS\Models\Vat;

$outputVat = Vat::create([
    'name' => "Standard Output Vat",
    'code' => "O",
    'rate' => 20,
    'account_id' => Account::create([
        'name' => "Sales VAT Account",
        'account_type' => Account::CONTROL,
    ])
]);

$inputVat = Vat::create([
    'name' => "Standard Input Vat",
    'code' => "I",
    'rate' => 10,
    'account_id' =>  Account::create([
        'name' => "Input VAT Account",
        'account_type' => Account::CONTROL,
    ])
]);
```

Now we'll set up some Accounts:

```php
use IFRS\Models\Account;

$bankAccount = Account::create([
    'name' => "Bank Account",
    'account_type' => Account::BANK,
]);

$revenueAccount = Account::create([
    'name' => "Sales Account",
    'account_type' => Account::OPERATING_REVENUE,
]);

$clientAccount = Account::create([
    'name' => "Example Client Account",
    'account_type' => Account::RECEIVABLE,
]);

$supplierAccount = Account::create([
    'name' => "Example Supplier Account",
    'account_type' => Account::PAYABLE,
]);

$opexAccount = Account::create([
    'name' => "Operations Expense Account",
    'account_type' => Account::OPERATING_EXPENSE,
]);

$assetAccount = Account::create([
    'name' => "Office Equipment Account",
    'account_type' => Account::NON_CURRENT_ASSET,
]);

```

Now we will create some Transactions in the Ledger, afterwards we will generate some reports. First though, it require a reporting period:

```php
use IFRS\Models\ReportingPeriod;

$period = ReportingPeriod::create([
    'period_count' => 1,
    'calendar_year' => 2022,
]);

```

Now that all Accounts are prepared, we can create the first Transaction, a Cash Sale:

```php
use IFRS\Transactions\CashSale;

$cashSale = CashSale::create([
    'account_id' => $bankAccount->id,
    'date' => Carbon::now(),
    'narration' => "Example Cash Sale",
]); // Intermediate save does not record the transaction in the Ledger
```
So far the Transaction has only one side of the double entry, so we create a Line Item for the other side:

```php
use IFRS\models\LineItem;

$cashSaleLineItem = LineItem::create([
    'account_id' => $revenueAccount->id,
    'narration' => "Example Cash Sale Line Item",
    'quantity' => 1,
    'amount' => 100,
]);

$cashSaleLineItem->addVat($outputVat);
$cashSale->addLineItem($cashSaleLineItem);
$cashSale->post(); // This posts the Transaction to the Ledger

```
The rest of the transactions:

```php
use IFRS\Transactions\ClientInvoice;

$clientInvoice = ClientInvoice::create([
    'account_id' => $clientAccount->id,
    'date' => Carbon::now(),
    'narration' => "Example Credit Sale",
]);

$clientInvoiceLineItem = LineItem::create([
    'account_id' => $revenueAccount->id,
    'narration' => "Example Credit Sale Line Item",
    'quantity' => 2,
    'amount' => 50,
]);

$clientInvoiceLineItem->addVat($outputVat);
$clientInvoice->addLineItem($clientInvoiceLineItem);

//Transaction save may be skipped as post() saves the Transaction automatically
$clientInvoice->post();

use IFRS\Transactions\CashPurchase;

$cashPurchase = CashPurchase::create([
    'account_id' => $clientAccount->id,
    'date' => Carbon::now(),
    'narration' => "Example Cash Purchase",
]);

$cashPurchaseLineItem = LineItem::create([
    'account_id' => $opexAccount->id,
    'narration' => "Example Cash Purchase Line Item",
    'quantity' => 4,
    'amount' => 25,
]);


$cashPurchaseLineItem->addVat($inputVat);
$cashPurchase->addLineItem($cashPurchaseLineItem)->post();

use IFRS\Transactions\SupplierBill;

$supplierBill = SupplierBill::create([
    'account_id' => $supplierAccount->id,
    'date' => Carbon::now(),
    'narration' => "Example Credit Purchase",
]);

$supplierBillLineItem = LineItem::create([
    'vat_id' => $inputVat->id,
    'account_id' => $assetAccount->id,
    'narration' => "Example Credit Purchase Line Item",
    'quantity' => 4,
    'amount' => 25,
]);

$supplierBillLineItem->addVat($inputVat);
$supplierBill->addLineItem($supplierBillLineItem)->post();

use IFRS\Transactions\ClientReceipt;

$clientReceipt = ClientReceipt::create([
    'account_id' => $clientAccount->id,
    'date' => Carbon::now(),
    'narration' => "Example Client Payment",
]);

$clientReceiptLineItem = LineItem::create([
    'account_id' => $bankAccount->id,
    'narration' => "Part payment for Client Invoice",
    'quantity' => 1,
    'amount' => 50,
]);

$clientReceipt->addLineItem($clientReceiptLineItem)->post();
```
We can assign the receipt to partially clear the Invoice above:

```php
use IFRS\Models\Assignment;

echo $clientInvoice->clearedAmount; //0: Currently the Invoice has not been cleared at all
echo $clientReceipt->balance; //50: The Receipt has not been assigned to clear any transaction

$assignment = Assignment::create([
    'assignment_date'=> Carbon::now(),
    'transaction_id' => $clientReceipt->id,
    'cleared_id' => $clientInvoice->id,
    'cleared_type'=> $clientInvoice->clearedType,
    'amount' => 50,
]);

echo $clientInvoice->clearedAmount; //50
echo $clientReceipt->balance; //0: The Receipt has been assigned fully to the Invoice

```

The Income Statement (Profit and Loss):

```php
use IFRS\Reports\IncomeStatement;

$incomeStatement = new IncomeStatement(
    "2021-01-01",   // Report start date
    "2021-12-31",   // Report end date
)->getSections();// Fetch balances from the ledger and store them internally

/**
* this function is only for demonstration and
* debugging use and should never be called in production
*/
dd($incomeStatement->toString());

Example Company
Income Statement
For the Period: Jan 01 2021 to Dec 31 2021

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
The Balance Sheet:

```php
use IFRS\Reports\BalanceSheet;

$balanceSheet = new BalanceSheet(
    "2021-12-31"  // Report end date
)->getSections();

/**
* again to emphasize, this function is only for demonstration and
* debugging use and should never be called in production
*/
dd($balanceSheet->toString());

Example Company
Balance Sheet
As at: Dec 31 2021

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

```php
use IFRS\Reports\AccountStatement;
use IFRS\Reports\AccountSchedule;

$transactions = new AccountStatement($clientAccount)->getTransactions();

dd($transactions);

array:2[
  ["transaction" => ClientInvoice, "debit" => 120, "credit" => 0, "balance" => 120],
  ["transaction" => ClientReceipt, "debit" => 0, "credit" => 50, "balance" => 70]
]

$transactions = new AccountSchedule($clientAccount, $currency)->getTransactions();

dd($transactions);

array:1[
  ["transaction" => ClientInvoice, "amount" => 120, "cleared" => 50, "balance" => 70],
]

```
## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## Getting Involved

I am acutely aware that as a professionally trained Accountant I may have used some conventions, definitions and styles that while seemingly obvious to me, might not be so clear to another developer. I would therefore welcome and greatly appreciate any feedback on the ease of use of the package so I can make it more useful to as many people as possible.


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b feature/fooBar`)
3. Write tests for the feature
3. Commit your changes (`git commit -am 'Add some fooBar'`)
4. Push to the branch (`git push origin feature/fooBar`)
5. Create a new Pull Request

## Roadmap

- [x] Add Cashflow Statement
- [x] Laravel 8 Compatibility
- [x] Add Multicurrency support
- [x] Expand Taxation Functionality

## License
This software is distributed for free under the MIT License

## References
* This package is heavily influenced by [chippyash/simple-accounts-3](https://github.com/chippyash/simple-accounts-3) and [scottlaurent/accounting](https://github.com/scottlaurent/accounting).
* Special thanks to [paschaldev](https://github.com/paschaldev) for his brilliant work in preventing collisions with already existing db tables.
