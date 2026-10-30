## Unreleased

- Fix `Balance::save()` translating the amount again on every save, which compounded a foreign currency Balance by its exchange rate each time it was edited, restored or otherwise saved. Only an amount being entered is translated now
- Fix `Assignment` allowing a transaction to be over cleared by less than 1. `bccomp()` was called without a scale, so it compared whole numbers only
- Scope `ReportingPeriod::transactionCurrencies()` to its Entity and ignore deleted Transactions. A foreign currency Transaction of another Entity, or a deleted one, could make `prepareBalancesTranslation()` fail
- Fix reports given an end date but no start date starting from the current year, so that any earlier end date came back empty. `AccountStatement`, `AccountSchedule` and `IncomeStatement` now start from the year of their end date, and `AgingSchedule` is no longer always empty for an earlier year
- `AccountSchedule` and `AgingSchedule` count only what had been cleared by their end date, using the new `clearedAmountAsAt()`. A later clearance used to reduce the amount outstanding
- Fix `IncomeStatement::getResults()` showing a loss as a profit. Its results now keep their sign, and its gross profit no longer includes non operating revenue, so they agree with `getSections()`
- Fix `BalanceSheet` reporting negative equity as positive
- Fix `Category::getAccountBalances()` leaving out opening balances when called without a start date
- Throw `EntityMismatch` when a report or balance query is given an `$entity` other than that of the logged in User. The `EntityScope` limits every query to the User's Entity, so these came back empty with no error. Passing an Entity with nobody logged in, as on the console, still works
- Fix `clearances` and `cleared_amount` being empty on Transaction subclasses such as `ClientInvoice`. They were looked up under the subclass name, and clearances are stored under `Transaction`
- Declare the properties that the reports created dynamically, which raises a deprecation notice from PHP 8.2
- Validate the date of a new or re-dated Balance against the start of its own Reporting Period rather than the current one, and refuse a new Balance, or a change to an amount or period, in a closed Reporting Period. Existing Balances can still be edited and restored otherwise
- Stop `Assignment::bulkAssign()` saving a zero Assignment once the Transaction's balance has run out
- Stop `Transaction::transactionNo()` counting the Transactions of later Reporting Periods
- Stop `Account::sectionBalances()` adding together Categories that share a name. The second is keyed `Name (id)`
- Add the `ifrs:audit-balances` command, which lists without changing anything the foreign currency Balances that may have been compounded, and the Transactions and Balances cleared by more than their amount

## 7.1.0 - 2026-10-30

- Fix `CashFlowStatement` ignoring its dates: sections and profit always covered the current year, so any other period reported nothing. Its profit is now that of the period rather than of the year to date, its starting cash is the cash held before the start date, and its end date is no longer pushed a day later
- Include the whole of the end date in `Account::currentBalance()`, and through it `closingBalance()`, `sectionBalances()` and `Category::getAccountBalances()`. A transaction made during the end date was left out, including on the last day of the year by default
- Stop `Account::sectionBalances()` counting a transaction on the start date in both the opening balance and the movement
- `AccountStatement` and `Account::sectionBalances()` treat their start date as a whole day
- Fix `Account::sectionBalances()` and so `CashFlowStatement` across two financial years, which took the opening balance of the later year and overstated cash. A section now opens with the balances of the year it starts in
- Default the start of a `CashFlowStatement` to the start of the year of its end date
- Identify closing Transactions by their Reporting Period and not their date, and ignore deleted ones. `Account::isClosed()` and `closingTransactions()` missed misdated closings, so preparing the translations again duplicated them, and an Account stayed closed after its closing Transaction was deleted
- Stop `ReportingPeriod::getTranslations()` and `postTranslations()` failing on a deleted closing Transaction
- Add the `ifrs:repair-balances` command, which lists Balances that a version before 7.0.0 attached to the Reporting Period of the next financial year, and moves them with `--apply`. Affects entities whose year does not start in January, for Balances saved without an explicit period before `year_start`. Run it without `--apply` first: a Balance given that period on purpose looks the same

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