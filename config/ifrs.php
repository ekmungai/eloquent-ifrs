<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Transaction;

use Ekmungai\IFRS\Reports\IncomeStatement;
use Ekmungai\IFRS\Reports\BalanceSheet;

return [

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    |
    | This configation maps Financial Statements account types to a coding scheme of your
    | choice such as the German SKr04 or Swedish BAS as well a defining the a glossary of
    | terms and names used in the application
    |
    */

    /*
     |--------------------------------------------------------------------------
     | Hashing Algorithm
     |--------------------------------------------------------------------------
     |
     | The Hashing Algorim to be used when hashing Ledger records.
     |
     */
    'hashing_algorithm' => env('HASHING_ALGORITHM', PASSWORD_DEFAULT),

    /*
     |--------------------------------------------------------------------------
     | Glossary
     |--------------------------------------------------------------------------
     |
     | Here you may specify the lables to be applied to the Accounts, Transactions and Statements.
     |
     */
    'accounts' => [
        //Balance Sheet: Assets Accounts
        Account::NON_CURRENT_ASSET => _("Non Current Asset"),
        Account::CONTRA_ASSET => _("Contra Asset"),
        Account::INVENTORY => _("Inventory"),
        Account::BANK => _("Bank"),
        Account::CURRENT_ASSET => _("Current Asset"),
        Account::RECEIVABLE => _("Receivable"),

        //Balance Sheet: Liabilities Accounts
        Account::NON_CURRENT_LIABILITY => _("Non Current Liability"),
        Account::CONTROL_ACCOUNT => _("Control Account"),
        Account::CURRENT_LIABILITY => _("Current Liability"),
        Account::PAYABLE => _("Payable"),
        Account::RECONCILIATION => _("Reconciliation"),

        //Balance Sheet: Equity Accounts
        Account::EQUITY => _("Equity"),

        //Income Statement: Operations Accounts
        Account::OPERATING_REVENUE => _("Operating Revenue"),
        Account::OPERATING_EXPENSE => _("Operating Expense"),

        //Income Statement: Non Operations Accounts
        Account::NON_OPERATING_REVENUE => _("Non Operating Revenue"),
        Account::DIRECT_EXPENSE => _("Direct Expense"),
        Account::OVERHEAD_EXPENSE => _("Overhead Expense"),
        Account::OTHER_EXPENSE => _("Other Expense"),
    ],

    'transactions' => [
        //client transactions
        Transaction::CS => _("Cash Sale"),
        Transaction::IN => _("Client Invoice"),
        Transaction::CN => _("Credit Note"),
        Transaction::RC => _("Client Receipt"),

        //supplier transactions
        Transaction::CP => _("Cash Purchase"),
        Transaction::BL => _("Supplier Bill"),
        Transaction::DN => _("Debit Note"),
        Transaction::PY => _("Supplier Payment"),

        //internal transactions
        Transaction::CE => _("Contra Entry"),
        Transaction::JN => _("Journal Entry"),
    ],

    'statements' => [
        // Income statement
        IncomeStatement::TITLE => _("Income Statement"),
        IncomeStatement::OPERATING_REVENUES => _("Operating Revenues"),
        IncomeStatement::NON_OPERATING_REVENUES => _("Non Operating Revenues"),
        IncomeStatement::OPERATING_EXPENSES => _("Operating Expenses"),
        IncomeStatement::NON_OPERATING_EXPENSES => _("Non Operating Expenses"),

        // Balance Sheet
        BalanceSheet::TITLE => _("Balance Sheet"),
        BalanceSheet::ASSETS => _("Assets"),
        BalanceSheet::LIABILITIES => _("Liabilities"),
        BalanceSheet::EQUITY => _("Equity"),
        BalanceSheet::RECONCILIATION => _("Reconciliation"),
    ],

    'balances' => [
        Balance::D => _('Debit'),
        Balance::C => _('Credit'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Balance Sheet Accounts
    |--------------------------------------------------------------------------
    |
    | Here you may specify the account code ranges for Balance Sheet accounts.
    |
    */

    BalanceSheet::ASSETS => [
        Account::NON_CURRENT_ASSET => 0, // 0 - 99
        Account::CONTRA_ASSET => 100, // 100 - 199
        Account::INVENTORY => 200, // 200 - 299
        Account::BANK => 300, // 300 - 399
        Account::CURRENT_ASSET => 400, // 400 - 499
        Account::RECEIVABLE => 500, // 500 - 1999
    ],
    BalanceSheet::LIABILITIES => [
        Account::NON_CURRENT_LIABILITY => 2000, // 2000 - 2099
        Account::CONTROL_ACCOUNT => 2100, // 2100 - 2199
        Account::CURRENT_LIABILITY => 2200, // 2200 - 2399
        Account::PAYABLE => 2400, // 2400 - 2999
    ],
    BalanceSheet::EQUITY => [
        Account::EQUITY => 3000, // 3000 - 3999
    ],

    /*
     |--------------------------------------------------------------------------
     | Income Statement Accounts
     |--------------------------------------------------------------------------
     |
     | Here you may specify the account code ranges for Income Statement accounts.
     |
     */

    IncomeStatement::OPERATING_REVENUES => [
        Account::OPERATING_REVENUE => 4000, // 4000 - 4499
    ],
    IncomeStatement::NON_OPERATING_REVENUES => [
        Account::NON_OPERATING_REVENUE => 4500, // 4500 - 4999
    ],
    IncomeStatement::OPERATING_EXPENSES => [
        Account::OPERATING_EXPENSE => 5000, // 5000 - 5999
    ],
    IncomeStatement::NON_OPERATING_EXPENSES => [
        Account::DIRECT_EXPENSE => 6000, // 6000 - 6999
        Account::OVERHEAD_EXPENSE => 7000, // 7000 - 7999
        Account::OTHER_EXPENSE => 8000, // 8000 - 8999
    ],

    /*
     |--------------------------------------------------------------------------
     | Reconciliation Accounts
     |--------------------------------------------------------------------------
     |
     | Here you may specify the account code ranges for Reconciliation accounts
     | such as suspense and misposting accounts.
     |
     */
    BalanceSheet::RECONCILIATION => [
        Account::RECONCILIATION => 9000, // 9000 - 9999
    ],

];
