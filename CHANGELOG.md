## Unreleased

- Fix `CashFlowStatement` ignoring its dates: sections and profit always covered the current year, so any other period reported nothing. Its profit is now that of the period rather than of the year to date, its starting cash is the cash held before the start date, and its end date is no longer pushed a day later
- Include the whole of the end date in `Account::currentBalance()`, and through it `closingBalance()`, `sectionBalances()` and `Category::getAccountBalances()`. A transaction made during the end date was left out, including on the last day of the year by default
- Stop `Account::sectionBalances()` counting a transaction on the start date in both the opening balance and the movement
- `AccountStatement` and `Account::sectionBalances()` treat their start date as a whole day

## 7.0.0 - 2026-10-30

- **BREAKING:** The `ifrs_create_or_update_users_table` migration no longer creates an application `users` table as a fallback. The table configured through `ifrs.user_model` must exist before the IFRS migrations run; the migration now fails with an explicit message instead of silently creating a competing table. Only the package's own prefixed table (`IFRS\User`) is still created for standalone use (#195)
- Make the users table extension additive and idempotent: only the columns the package is missing are added, and `down()` drops only those columns instead of the application's table (#195)
- Match the `ifrs_recycled_objects.user_id` column to the actual primary key of the users table. Laravel 11 reports native database types from `Schema::getColumnType()`, so `char`, `varchar`, `uuid` and the driver specific integer names are now all mapped to a compatible column, fixing the incompatible `bigint unsigned` foreign key against UUID and integer primary keys (#195)
- Fix `ifrs_recycled_objects.recyclable_id`, which was typed from the users table primary key even though it references IFRS models, whose keys are always big integers
- Support a custom primary key name on the configured User model
- Remove the stray indentation that made the users migration emit whitespace before its opening PHP tag
- Fix posting a Transaction whose Line Item carries a zero rated Vat, which failed with a not null constraint violation on `ifrs_ledgers.folio_account`. A zero rated Vat is charged no amount and, by design, has no Vat account, so it now posts no Vat Ledgers
- Stop the report tests generating zero Line Item quantities and amounts of their own. `LineItemFactory` was guarded in 6.0.0, but `TrialBalanceTest`, `AccountTest` and `CategoryTest` build Line Items inline with `randomNumber()`, which returns zero for about one draw in eighty, zeroing an account balance and dropping the section the test then indexes
- Stop `AccountTest::testAccountClosing` drawing its "different" year from the `ReportingPeriod` factory. Faker's `year()` spans 1970 to the current year and `unique()` only excludes values it returned itself, so roughly one run in 75 drew the current year, which is closed, and the assertion that it is not failed
- Normalise line endings through a `.gitattributes` so the repository stores LF whatever platform a change is authored on. A repo wide CRLF conversion had previously turned routine edits into whole file diffs
- **BREAKING:** Remove three exception classes that nothing throws any more: `InvalidVatRate`, orphaned when the compound Journal Entry Vat guard was replaced by `MultipleVatError`; `UnauthorizedUser`, orphaned when models stopped requiring an authenticated user; and `VatPeriodOverlap`, whose Vat validity period check was never reimplemented after the Vat account moved off the Line Item. Catch `IFRSException` instead
- **BREAKING:** Fix balances for an Entity whose financial year does not start in January. A year was resolved from its 1 January, which belongs to the previous financial year, so `Account::openingBalance()` read the wrong Reporting Period, or threw `MissingReportingPeriod` when there was none. `closingBalance()`, `openingBalances()`, `sectionBalances()` and the reports built on them inherited the error. Reported figures change for these entities (#187)
- Fix the same assumption in `TrialBalance`, `Entity::current_reporting_period`, `Account::isClosed()`, `Account::closingTransactions()`, `ReportingPeriod::transactionCurrencies()` and `ReportingPeriod::prepareBalancesTranslation()`, the last of which also passed a malformed date. A financial year is labelled by the calendar year it starts in
- Add `ReportingPeriod::yearStart()`, the first day of the financial year that a calendar year labels
- Include movements between the start of the year and the statement's start date in the `AccountStatement` opening balance (#187)
- Stop the currency filter of `Account::openingBalance()` and `AccountSchedule` dropping the Reporting Period filter, which added together the balances of every year
- Add `forex_scale` to the exchange rate columns: the rate columns now use the `forex_scale` setting for their decimal places, so rates such as IDR to EUR are stored without being rounded away. Set it before running the migrations; changing it later needs a migration of the existing columns. Rates below 0.0001 no longer crash the Assignment rate comparison (#201)
- Update `league/commonmark` and `guzzlehttp` dependencies past published advisories, and set `prefer-stable` (#202)
## 6.0.0 - 2026-07-01

- Add Laravel 13 Compatibility
- **BREAKING:** Raise minimum PHP requirement to 8.2 (PHP 8.1 reached end of life)
- **BREAKING:** Drop support for Laravel 10 and PHP 8.1; supported range is now Laravel 11, 12 and 13
- Update dev dependencies for the supported range (orchestra/testbench ^9|^10|^11, nunomaduro/collision ^8, phpunit ^11|^12, spatie/laravel-ignition ^2)
- Fix the `remove_vat_id_column` migration to drop the foreign key before the column on every driver (modern SQLite rejects the dangling foreign key during the Laravel 11+ table rebuild)
- Fix `AccountSchedule` age calculation for Carbon 3, which returns a signed float from `diffInDays()` instead of a whole number
- Modernise `phpunit.xml` to the PHPUnit 10+ schema (`<source>` element; remove attributes dropped in PHPUnit 10)
- Make the test suite deterministic: seed the global PRNG via a test bootstrap and pin the execution order, eliminating intermittent failures caused by Faker drawing from a process-wide random stream shared across tests
- Stop the `LineItem` and `Vat` factories from generating zero `amount`/`quantity`/`rate` values, which produced zero-balance accounts that reports legitimately omit and caused the Trial Balance test to fail intermittently when a differing dependency tree (e.g. the PHP 8.2 CI leg) shifted the shared random stream
- Test against PHP 8.2, 8.3 and 8.4 in CI via a build matrix
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