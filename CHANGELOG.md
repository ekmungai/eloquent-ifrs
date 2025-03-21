## 5.0.4 - 2025-03-21

- Add Laravel 12 Compatibility
## 5.0.3 - 2024-03-26

- Add Laravel 11 Compatibility
## 5.0.2 - 2024-02-19

- Add Attachments to Transactions
## 5.0.1 - 2023-04-18

- Add Laravel 10 Compatibility
## 5.0.0 - 2021-04-20

- Add Laravel 9 Compatibility
- Minimum PHP 8.0 requirement
- Add compound VAT support
## 4.1.0 - 2021-06-22

- Add Forex Difference transactions during clearing 
- Add Forex Balance Translation at year closing
## 4.0.1 - 2021-04-28

- Adapt migrations to accomodate Lumen installations
- Adapt Readme with instructions for Lumen installations
## 4.0.0 - 2021-04-20

- Laravel 8 Compatibility
- Minimum PHP 7.3 requirement
## 3.1.4 - 2021-04-05

- Prevent transaction dates at the beginning of the first day of the reporting period
- Include credited attribute to getTransaction method
## 3.1.3 - 2021-04-04

- Reset composer.json dependencies to laravel 6 compatibility
## 3.1.2 - 2021-04-02

- Add Carbons `->startOfDay()` to reports start date so that all transactions since midnight are included
## 3.1.1 - 2021-03-31

- Remove hard coded current date as Account closing balance End Date
## 3.1.0 - 2021-03-30

- Add Localization to Entity Model
- Add monthly aggregates function to Income Statement
- Various fixes to Reports
- Fix for User Entity relationship
## 3.0.0 - 2021-01-26

- Include Cash Flow Statement
- Move Vat Account relation from Line Item model to Vat Model
- Enable daughter Entities
- Add mid year Opening Balances
- Add sub totals to Financial Statement totals
- Enable bulk assignments

## 2.0.1 - 2020-05-25

- Remove forced ugtext translation

## 2.0.0 - 2020-05-25

- DB table prefixes defined in configuration file
- Auth model defined in configuration file

## 1.1.1 - 2020-05-23
- changed user migration to only modify existing users table
- added scope to database table names to prevent conflict with existing tables in parent application

## 1.1.0 - 2020-05-21
- add aging balances report
- add Assignable Transaction bulk assignment
- add exceptions for posted transactions Line Items add/remove/change

## 1.0.1 - 2020-05-18

- revise minimum eloquent version to `6.0.0` to enable compatibility with eloquent `7.0.0`

## 1.0.0 - 2020-04-19

- initial release