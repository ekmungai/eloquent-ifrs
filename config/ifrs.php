<?php

/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Transaction;
use IFRS\Models\ReportingPeriod;

use IFRS\Reports\IncomeStatement;
use IFRS\Reports\BalanceSheet;
use IFRS\Reports\TrialBalance;
use IFRS\Reports\CashFlowStatement;

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
     | DB table prefix
     |--------------------------------------------------------------------------
     |
     | Database table prefix to prevent collision during migration
     |
     */
    'table_prefix' => 'ifrs_',

    /*
     |--------------------------------------------------------------------------
     | User model
     |--------------------------------------------------------------------------
     |
     | Eloquent model for the users. This assumes you already have a working
     | user model. If not, create one and reference it here. During initial
     | migration, columns are added to the table name. Your users model should
     | also use the IFRSUser trait to provide access to the entity scope
     |
     */
    'user_model' => App\User::class,

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
     | Aging Schedule Brackets
     |--------------------------------------------------------------------------
     |
     | The time periods segments for the Aging Schedule.
     |
     */
    'aging_schedule_brackets' => [
        'current'           => 30,
        '31 - 90 days'      => 90,
        '91 - 180 days'     => 180,
        '181 - 270 days'    => 270,
        '271 - 365 days'    => 365,
        '365+ (bad debts)' =>  null,
    ],

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
        Account::NON_CURRENT_ASSET => 'Non Current Asset',
        Account::CONTRA_ASSET => 'Contra Asset',
        Account::INVENTORY => 'Inventory',
        Account::BANK => 'Bank',
        Account::CURRENT_ASSET => 'Current Asset',
        Account::RECEIVABLE => 'Receivable',

        //Balance Sheet: Liabilities Accounts
        Account::NON_CURRENT_LIABILITY => 'Non Current Liability',
        Account::CONTROL => 'Control',
        Account::CURRENT_LIABILITY => 'Current Liability',
        Account::PAYABLE => 'Payable',
        Account::RECONCILIATION => 'Reconciliation',

        //Balance Sheet: Equity Accounts
        Account::EQUITY => 'Equity',

        //Income Statement: Operations Accounts
        Account::OPERATING_REVENUE => 'Operating Revenue',
        Account::OPERATING_EXPENSE => 'Operating Expense',

        //Income Statement: Non Operations Accounts
        Account::NON_OPERATING_REVENUE => 'Non Operating Revenue',
        Account::DIRECT_EXPENSE => 'Direct Expense',
        Account::OVERHEAD_EXPENSE => 'Overhead Expense',
        Account::OTHER_EXPENSE => 'Other Expense',
    ],

    'transactions' => [
        //client transactions
        Transaction::CS => 'Cash Sale',
        Transaction::IN => 'Client Invoice',
        Transaction::CN => 'Credit Note',
        Transaction::RC => 'Client Receipt',

        //supplier transactions
        Transaction::CP => 'Cash Purchase',
        Transaction::BL => 'Supplier Bill',
        Transaction::DN => 'Debit Note',
        Transaction::PY => 'Supplier Payment',

        //internal transactions
        Transaction::CE => 'Contra Entry',
        Transaction::JN => 'Journal Entry',
    ],

    'statements' => [
        // Income statement
        IncomeStatement::TITLE => 'Income Statement',
        IncomeStatement::OPERATING_REVENUES => 'Operating Revenues',
        IncomeStatement::NON_OPERATING_REVENUES => 'Non Operating Revenues',
        IncomeStatement::OPERATING_EXPENSES => 'Operating Expenses',
        IncomeStatement::NON_OPERATING_EXPENSES => 'Non Operating Expenses',

        // Balance Sheet
        BalanceSheet::TITLE => 'Balance Sheet',
        BalanceSheet::ASSETS => 'Assets',
        BalanceSheet::LIABILITIES => 'Liabilities',
        BalanceSheet::EQUITY => 'Equity',
        BalanceSheet::RECONCILIATION => 'Reconciliation',

        // Trial Balance
        TrialBalance::TITLE => 'Trial Balance',

        // Cash Flow Statement
        CashFlowStatement::TITLE => 'Cash Flow Statement',
        CashFlowStatement::PROVISIONS => 'Provisions',
        CashFlowStatement::RECEIVABLES => 'Receivables',
        CashFlowStatement::PAYABLES => 'Payables',
        CashFlowStatement::CURRENT_ASSETS => 'Current Assets',
        CashFlowStatement::CURRENT_LIABILITIES => 'Current Liabilities',
        CashFlowStatement::TAXATION => 'Taxation',
        CashFlowStatement::NON_CURRENT_ASSETS => 'Non Current Assets',
        CashFlowStatement::NON_CURRENT_LIABILITIES => 'Non Current Liabilities',
        CashFlowStatement::EQUITY => 'Equity',
        CashFlowStatement::PROFIT => 'Net Profit',
        CashFlowStatement::START_CASH_BALANCE => 'Beginning Cash balance',
        CashFlowStatement::END_CASH_BALANCE => 'Ending Cash balance',
    ],

    'balances' => [
        Balance::DEBIT => 'Debit',
        Balance::CREDIT => 'Credit',
    ],

    'reporting_period_status' => [
        ReportingPeriod::OPEN => 'Open',
        ReportingPeriod::CLOSED => 'Closed',
        ReportingPeriod::ADJUSTING => 'Adjusting',
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
        Account::CONTROL => 2100, // 2100 - 2199
        Account::CURRENT_LIABILITY => 2200, // 2200 - 2399
        Account::PAYABLE => 2400, // 2400 - 2999
    ],
    BalanceSheet::EQUITY => [
        Account::EQUITY => 3000, // 3000 - 3999
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
     | Cash Flow Statement Accounts
     |--------------------------------------------------------------------------
     |
     | Here you may specify the account code ranges for Cash Flow Statement accounts.
     |
     */

    CashFlowStatement::PROVISIONS => [
        Account::CONTRA_ASSET => 100, // 100 - 199
    ],
    CashFlowStatement::RECEIVABLES => [
        Account::RECEIVABLE => 200, // 200 - 299
    ],
    CashFlowStatement::PAYABLES => [
        Account::PAYABLE => 2400, // 2400 - 2999
    ],
    CashFlowStatement::CURRENT_ASSETS => [
        Account::INVENTORY => 200, // 200 - 299
        Account::CURRENT_ASSET => 400, // 400 - 499
    ],
    CashFlowStatement::CURRENT_LIABILITIES => [
        Account::CURRENT_LIABILITY => 2200, // 2200 - 2399
    ],
    CashFlowStatement::TAXATION => [
        Account::CONTROL => 2200, // 2200 - 2399
    ],
    CashFlowStatement::NON_CURRENT_ASSETS => [
        Account::NON_CURRENT_ASSET => 0, // 0 - 99
    ],
    CashFlowStatement::NON_CURRENT_LIABILITIES => [
        Account::NON_CURRENT_LIABILITY => 2000, // 2000 - 2099
    ],
    CashFlowStatement::EQUITY => [
        Account::EQUITY => 3000, // 3000 - 3999
    ],
];
