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
    | terms and names used in the application among other settings
    |
    */

    /*
     |--------------------------------------------------------------------------
     | User Model
     |--------------------------------------------------------------------------
     |
     | User model used by the application
     |
     */
    'user_model' => 'App\Models\User',
    
    /*
     |--------------------------------------------------------------------------
     | Locales
     |--------------------------------------------------------------------------
     |
     | These are the locales whose currencies can be localized for reporting. At least one
     | locale must be specified to serve as the default
     |
     */
    'locales' => [
        'en_GB',
        'ar_BH'
    ],

    /*
     |--------------------------------------------------------------------------
     | Forex scale
     |--------------------------------------------------------------------------
     |
     | The number of decimal places to consider when calculating the difference between two 
     | exchange rates
     |
     */
    'forex_scale' => 4,

    /*
     |--------------------------------------------------------------------------
     | Single Currency account types
     |--------------------------------------------------------------------------
     |
     | Accounts of the types defined here will reject balances and transactions of a currency  
     | different from the their own
     |
     */
    'single_currency' => [
        Account::BANK
    ],

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
     | DB load migrations
     |--------------------------------------------------------------------------
     |
     | Database load migrations automatic
     |
     */
     'load_migrations' => true,

	/*
     |--------------------------------------------------------------------------
     | DB load factories
     |--------------------------------------------------------------------------
     |
     | Database load factories automatic
     |
     */
     'load_factories' => true,
	 
    /*
     |--------------------------------------------------------------------------
     | Hashing Algorithm
     |--------------------------------------------------------------------------
     |
     | The Hashing Algorim to be used when hashing Ledger records.
     |
     */
    'hashing_algorithm' => env('HASHING_ALGORITHM', 'sha256'),

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
    | Accounts Codes
    |--------------------------------------------------------------------------
    |
    | Here you may specify the account code ranges forAccounts.
    |
    */

    'account_codes' => [

        // BALANCE SHEET 
        // =============

        // Asset Accounts
        Account::NON_CURRENT_ASSET => 0, // 0 - 99
        Account::CONTRA_ASSET => 100, // 100 - 199
        Account::INVENTORY => 200, // 200 - 299
        Account::BANK => 300, // 300 - 399
        Account::CURRENT_ASSET => 400, // 400 - 499
        Account::RECEIVABLE => 500, // 500 - 1999

        // Liability Accounts
        Account::NON_CURRENT_LIABILITY => 2000, // 2000 - 2099
        Account::CONTROL => 2100, // 2100 - 2199
        Account::CURRENT_LIABILITY => 2200, // 2200 - 2399
        Account::PAYABLE => 2400, // 2400 - 2999

        // Equity Accounts
        Account::EQUITY => 3000, // 3000 - 3999

        // INCOME STATEMENT 
        // ================

        // Operating Revenue Accounts
        Account::OPERATING_REVENUE => 4000, // 4000 - 4499

        // Non Operating Revenue Accounts
        Account::NON_OPERATING_REVENUE => 4500, // 4500 - 4999

        // Operating Expense Accounts
        Account::OPERATING_EXPENSE => 5000, // 5000 - 5999

        // Non Operating Expense Accounts
        Account::DIRECT_EXPENSE => 6000, // 6000 - 6999
        Account::OVERHEAD_EXPENSE => 7000, // 7000 - 7999
        Account::OTHER_EXPENSE => 8000, // 8000 - 8999

        // RECONCILIATION 
        // ================

        // Reconciliation Accounts
        Account::RECONCILIATION => 9000, // 9000 - 9999
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

        // Balance Sheet: Asset Accounts
        Account::NON_CURRENT_ASSET => 'Non Current Asset',
        Account::CONTRA_ASSET => 'Contra Asset',
        Account::INVENTORY => 'Inventory',
        Account::BANK => 'Bank',
        Account::CURRENT_ASSET => 'Current Asset',
        Account::RECEIVABLE => 'Receivable',

        // Balance Sheet: Liabilities Accounts
        Account::NON_CURRENT_LIABILITY => 'Non Current Liability',
        Account::CONTROL => 'Control',
        Account::CURRENT_LIABILITY => 'Current Liability',
        Account::PAYABLE => 'Payable',
        Account::RECONCILIATION => 'Reconciliation',

        // Balance Sheet: Equity Accounts
        Account::EQUITY => 'Equity',

        // Income Statement: Operations Accounts
        Account::OPERATING_REVENUE => 'Operating Revenue',
        Account::OPERATING_EXPENSE => 'Operating Expense',

        // Income Statement: Non Operations Accounts
        Account::NON_OPERATING_REVENUE => 'Non Operating Revenue',
        Account::DIRECT_EXPENSE => 'Direct Expense',
        Account::OVERHEAD_EXPENSE => 'Overhead Expense',
        Account::OTHER_EXPENSE => 'Other Expense',
    ],

    'transactions' => [
        // Client Transactions
        Transaction::CS => 'Cash Sale',
        Transaction::IN => 'Client Invoice',
        Transaction::CN => 'Credit Note',
        Transaction::RC => 'Client Receipt',

        //Supplier Transactions
        Transaction::CP => 'Cash Purchase',
        Transaction::BL => 'Supplier Bill',
        Transaction::DN => 'Debit Note',
        Transaction::PY => 'Supplier Payment',

        // Internal Transactions
        Transaction::CE => 'Contra Entry',
        Transaction::JN => 'Journal Entry',
    ],

    'statements' => [
        // Income statement: Sections
        IncomeStatement::TITLE => 'Income Statement',
        IncomeStatement::OPERATING_REVENUES => 'Operating Revenues',
        IncomeStatement::NON_OPERATING_REVENUES => 'Non Operating Revenues',
        IncomeStatement::OPERATING_EXPENSES => 'Operating Expenses',
        IncomeStatement::NON_OPERATING_EXPENSES => 'Non Operating Expenses',

        // Income statement: Results
        IncomeStatement::GROSS_PROFIT => 'Gross Profit',
        IncomeStatement::TOTAL_REVENUE => 'Total Revenue',
        IncomeStatement::TOTAL_EXPENSES => 'Total Expenses',
        IncomeStatement::NET_PROFIT => 'Net Profit',

        // Balance Sheet: Sections
        BalanceSheet::TITLE => 'Balance Sheet',
        BalanceSheet::ASSETS => 'Assets',
        BalanceSheet::LIABILITIES => 'Liabilities',
        BalanceSheet::EQUITY => 'Equity',
        BalanceSheet::RECONCILIATION => 'Reconciliation',

        // Balance Sheet: Results
        BalanceSheet::TOTAL_ASSETS => 'Total Assets',
        BalanceSheet::TOTAL_LIABILITIES => 'Total Liabilities',
        BalanceSheet::NET_ASSETS => 'Net Assets',
        BalanceSheet::TOTAL_RECONCILIATION => 'Total Reconciliation',
        BalanceSheet::TOTAL_EQUITY => 'Total Equity',

        // Trial Balance
        TrialBalance::TITLE => 'Trial Balance',

        // Cash Flow Statement: Sections
        CashFlowStatement::PROVISIONS => 'Provisions',
        CashFlowStatement::RECEIVABLES => 'Receivables',
        CashFlowStatement::PAYABLES => 'Payables',
        CashFlowStatement::CURRENT_ASSETS => 'Current Assets',
        CashFlowStatement::CURRENT_LIABILITIES => 'Current Liabilities',
        CashFlowStatement::TAXATION => 'Taxation',
        CashFlowStatement::NON_CURRENT_ASSETS => 'Non Current Assets',
        CashFlowStatement::NON_CURRENT_LIABILITIES => 'Non Current Liabilities',
        CashFlowStatement::EQUITY => 'Equity',

        // Cash Flow Statement: Headings
        CashFlowStatement::TITLE => 'Cash Flow Statement',
        CashFlowStatement::OPERATIONS_CASH_FLOW => 'Operations Cash Flow',
        CashFlowStatement::INVESTMENT_CASH_FLOW => 'Investment Cash Flow',
        CashFlowStatement::FINANCING_CASH_FLOW => 'Financing Cash Flow',
        CashFlowStatement::NET_CASH_FLOW => 'Net Cash Flow',

        // Cash Flow Statement: Results
        CashFlowStatement::PROFIT => 'Net Profit',
        CashFlowStatement::START_CASH_BALANCE => 'Beginning Cash balance',
        CashFlowStatement::END_CASH_BALANCE => 'Ending Cash balance',
        CashFlowStatement::CASHBOOK_BALANCE => 'Cashbook balance',
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
    | Balance Sheet Sections
    |--------------------------------------------------------------------------
    |
    | Here you may specify the Balance Sheet Sections.
    |
    */

    BalanceSheet::ASSETS => [
        Account::NON_CURRENT_ASSET,
        Account::CONTRA_ASSET,
        Account::INVENTORY,
        Account::BANK,
        Account::CURRENT_ASSET,
        Account::RECEIVABLE,
    ],
    BalanceSheet::LIABILITIES => [
        Account::NON_CURRENT_LIABILITY,
        Account::CONTROL,
        Account::CURRENT_LIABILITY,
        Account::PAYABLE,
    ],
    BalanceSheet::EQUITY => [
        Account::EQUITY
    ],
    BalanceSheet::RECONCILIATION => [
        Account::RECONCILIATION
    ],

    /*
     |--------------------------------------------------------------------------
     | Income Statement Sections
     |--------------------------------------------------------------------------
     |
     | Here you may specify the Income Statement sections.
     |
     */

    IncomeStatement::OPERATING_REVENUES => [
        Account::OPERATING_REVENUE
    ],
    IncomeStatement::NON_OPERATING_REVENUES => [
        Account::NON_OPERATING_REVENUE
    ],
    IncomeStatement::OPERATING_EXPENSES => [
        Account::OPERATING_EXPENSE
    ],
    IncomeStatement::NON_OPERATING_EXPENSES => [
        Account::DIRECT_EXPENSE,
        Account::OVERHEAD_EXPENSE,
        Account::OTHER_EXPENSE
    ],

    /*
     |--------------------------------------------------------------------------
     | Cash Flow Statement Sections
     |--------------------------------------------------------------------------
     |
     | Here you may specify the Cash Flow Statement sections.
     |
     */

    CashFlowStatement::PROVISIONS => [
        Account::CONTRA_ASSET
    ],
    CashFlowStatement::RECEIVABLES => [
        Account::RECEIVABLE
    ],
    CashFlowStatement::PAYABLES => [
        Account::PAYABLE
    ],
    CashFlowStatement::CURRENT_ASSETS => [
        Account::INVENTORY,
        Account::CURRENT_ASSET
    ],
    CashFlowStatement::CURRENT_LIABILITIES => [
        Account::CURRENT_LIABILITY,
        Account::RECONCILIATION
    ],
    CashFlowStatement::TAXATION => [
        Account::CONTROL
    ],
    CashFlowStatement::NON_CURRENT_ASSETS => [
        Account::NON_CURRENT_ASSET
    ],
    CashFlowStatement::NON_CURRENT_LIABILITIES => [
        Account::NON_CURRENT_LIABILITY
    ],
    CashFlowStatement::EQUITY => [
        Account::EQUITY
    ],
    CashFlowStatement::NET_CASH_FLOW => [
        Account::BANK
    ],
];
