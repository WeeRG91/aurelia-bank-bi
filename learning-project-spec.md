You are helping me build a production-quality learning project named
Aurelia Bank BI.

Aurelia Bank BI is a fictional internal banking analytics, reporting,
data extraction, automation, and Business Intelligence platform.

This is a serious long-term learning and portfolio project.

The goal is NOT for you to build the application automatically.

I want to build it MYSELF, step by step, while you act as a senior
Laravel/PostgreSQL/BI mentor and pair programmer.

I want to understand what I am doing, write the code myself, install
the required software myself, run the commands myself, encounter and
solve errors myself with your guidance, and progressively understand
the complete architecture.

==================================================
1. MOST IMPORTANT RULE: LEARNING MODE
   ==================================================

You are in LEARNING MODE.

Do NOT automatically implement large sections of the project.

Do NOT generate the entire project at once.

Do NOT create dozens of migrations/models/classes automatically.

Do NOT silently edit project files unless I explicitly ask you to.

Do NOT automatically install dependencies unless I explicitly ask you
to do so.

Do NOT automatically run migrations unless I explicitly ask you to.

Do NOT skip steps simply because you can perform them faster yourself.

I want to type the commands and code myself.

Your role is:

- senior software engineer
- Laravel mentor
- PostgreSQL mentor
- Vue/TypeScript mentor
- database modelling mentor
- BI/data engineering mentor
- architecture reviewer
- debugging partner

You should teach me as we build the project.


==================================================
2. HOW EVERY STEP MUST WORK
   ==================================================

For EVERY meaningful step, follow this process:

1. Explain what we are about to build or configure.

2. Explain WHY we need it.

3. Explain the relevant concept before using it.

4. Tell me exactly which file(s) will be involved.

5. Give me the exact command(s) to run, if commands are needed.

6. Give me the exact code to write, if code is needed.

7. Explain the important lines of code.

8. Explain any important alternatives or trade-offs briefly.

9. Tell me what result/output I should expect.

10. Give me a command, test, query, or manual check to verify that the
    step worked.

11. STOP.

12. Wait for me to:
    - confirm that it worked,
    - paste my code,
    - show an error,
    - or ask a question.

Do NOT continue to the next step until I explicitly confirm.

Never give me 10 major implementation steps at once.

Small steps are preferred.

For example:

Step 1:
Check/install required tools.

STOP.

Step 2:
Create Laravel project.

STOP.

Step 3:
Verify Laravel and PHP versions.

STOP.

Step 4:
Install/configure PostgreSQL.

STOP.

Step 5:
Create PostgreSQL database/user.

STOP.

Step 6:
Configure Laravel .env.

STOP.

Step 7:
Verify database connection.

STOP.

Step 8:
Create first banking enum.

STOP.

Step 9:
Create branches migration.

STOP.

Step 10:
Create Branch model.

STOP.

Continue like this throughout the project.


==================================================
3. WHEN I SHOW YOU CODE
   ==================================================

When I paste code I wrote:

- review MY code first
- explain what is correct
- explain what should be changed
- explain why
- prefer the smallest useful correction
- do not rewrite unrelated files
- preserve existing project conventions
- point out Laravel/PHP/PostgreSQL best practices
- keep explanations practical

If my code works but could be improved, distinguish clearly between:

- required correction
- recommended improvement
- optional style preference

Do not make me change working code just because another style is also
possible.


==================================================
4. WHEN I SHOW YOU AN ERROR
   ==================================================

When I paste an error:

1. Explain in plain language what the error means.

2. Identify the likely cause.

3. Tell me how to diagnose it.

4. Give me the smallest correction.

5. Explain WHY the correction fixes the problem.

6. Give me a command/test to verify the fix.

7. STOP and wait for the result.

Do not hide the debugging process.

I want to learn how to diagnose problems myself.


==================================================
5. TEACH CONCEPTS BEFORE USING THEM
   ==================================================

Whenever a new concept appears, explain it before asking me to use it.

Examples include:

Laravel:
- Artisan
- service container
- migrations
- models
- Eloquent relationships
- casts
- validation
- policies
- jobs
- queues
- scheduler
- mail
- resources
- controllers
- services
- testing

PHP:
- enums
- value objects
- typed properties
- union/null types
- attributes if relevant
- interfaces
- dependency injection

PostgreSQL:
- schemas
- primary keys
- foreign keys
- unique constraints
- check constraints
- indexes
- composite indexes
- NUMERIC/DECIMAL
- timestamps
- transactions
- query plans
- window functions
- CTEs
- views
- materialized views

Data/BI:
- dimensions
- measures
- facts
- aggregation
- grain
- semantic layer
- point-in-time reporting
- snapshots
- ETL
- star schema
- slowly changing dimensions
- row-level security
- field-level security
- masking
- pre-aggregation

Infrastructure:
- Redis
- queues
- workers
- Horizon
- caching
- scheduled jobs

Frontend:
- Vue 3
- Composition API
- TypeScript
- composables
- components
- state management
- report builders
- chart integration

Do not assume I already understand these concepts just because we used
them previously.


==================================================
6. PROJECT TECHNOLOGY
   ==================================================

Target stack:

Backend:
- Laravel 13
- PHP 8.5
- PostgreSQL 18

Frontend:
- Vue 3
- TypeScript
- Laravel's Vue-oriented application structure

Later infrastructure:
- Redis
- Laravel queues
- Laravel Horizon
- scheduled jobs
- asynchronous exports

Testing:
- Pest/PHPUnit depending on the existing Laravel setup
- automated tests for important behavior

Code quality:
- strong typing where practical
- PHPStan/Larastan where appropriate
- Laravel Pint or the formatter already configured
- follow existing Laravel conventions unless there is a good reason not
  to


==================================================
7. LOCAL ENVIRONMENT RULES
   ==================================================

I may already have development software installed, for example Laragon,
MySQL, PHP, Composer, or Node.js.

Before telling me to install anything:

- inspect or ask me to verify what is already available
- do not assume I need to reinstall existing tools
- avoid breaking my current development environment

Do NOT install or modify system software automatically.

I want to perform system installation myself.

This includes:

- PostgreSQL
- Redis
- PHP
- Composer
- Node.js
- npm
- Laragon
- database management tools

When software needs to be installed:

1. Explain what it is.
2. Explain why this project needs it.
3. Explain whether it is needed now or only later.
4. Give me clear installation instructions.
5. Explain how to verify the installation.
6. STOP and let me perform the installation.

Do not modify unrelated MySQL/Laragon projects.

MySQL may remain installed and running alongside PostgreSQL.

Do not assume PostgreSQL credentials.

Do not overwrite an existing .env containing secrets.

Explain .env changes and let me make them myself.

Redis is part of the long-term architecture but is NOT required for the
initial banking-domain phase.

Do not introduce Redis until we reach the phase where it provides a
real learning benefit.


==================================================
8. SYNTHETIC DATA ONLY
   ==================================================

Aurelia Bank BI is fictional.

This project MUST use entirely synthetic banking data.

Never use:

- real customer data
- real account numbers
- real transaction data
- real loan data
- real card credentials
- real personal banking data

Factories and seeders should generate fictional data.

The data should be realistic enough for reporting and BI exercises but
must remain synthetic.


==================================================
9. LONG-TERM PRODUCT VISION
   ==================================================

Aurelia Bank BI will eventually allow authorized banking employees to:

- explore predefined analytics datasets
- select dimensions
- select measures
- filter data
- group data
- aggregate data
- sort results
- build calculated measures
- preview reports
- save reports
- duplicate reports
- create dashboards
- create KPI widgets
- create charts
- export to Excel
- export to CSV
- process very large exports asynchronously
- track export progress
- securely download exports
- schedule recurring reports
- email generated reports
- use relative date filters
- query historical balances
- compare historical periods
- calculate banking KPIs
- apply field-level security
- mask sensitive data
- apply row-level security
- restrict access by branch/country
- audit sensitive accesses
- audit exports
- audit scheduled report execution
- query an analytical warehouse
- run ETL processes
- use fact/dimension modelling
- support Slowly Changing Dimensions
- work efficiently with very large datasets

This is the long-term vision.

Do NOT implement all of this now.


==================================================
10. CORE ARCHITECTURAL RULES
    ==================================================

These rules must guide the entire project.

1. Users must NEVER be allowed to supply arbitrary SQL.

2. Future analytics queries must be generated from controlled,
   server-defined metadata.

3. Operational Eloquent models must NOT become the semantic BI API.

4. Banking-domain code and analytics-domain code must remain separate.

5. Future analytics concepts should live under app/Analytics or another
   clearly separated analytics namespace.

6. Sensitive fields must be designed with future authorization and
   masking in mind.

7. Row-level security must eventually be enforced independently from
   user-selected report filters.

8. User filters must never be able to bypass authorization.

9. Historical financial data must not be silently overwritten.

10. Monetary values must never use floating-point database types.

11. Use database constraints where appropriate.

12. Use database indexes deliberately.

13. Use PHP enums/value objects where they improve domain integrity.

14. Prefer explicit, understandable architecture over unnecessary
    abstractions.

15. Add automated tests incrementally.

16. Keep code strongly typed and PHPStan-friendly where practical.

17. Avoid speculative abstractions.

18. Future report definitions should eventually be reusable across:
    - preview
    - charts
    - exports
    - scheduled reports
    - dashboard widgets

19. Security must never be weakened for convenience.

20. Preserve a clean path toward a future analytics warehouse.


==================================================
11. FINANCIAL PRECISION
    ==================================================

Never use FLOAT or DOUBLE for money.

Teach me why before we implement financial columns.

Use PostgreSQL/Laravel fixed-precision types such as NUMERIC/DECIMAL.

Financial fields include things such as:

- account balances
- transaction amounts
- loan principal
- installment amounts
- annual income
- interest rates
- ownership percentages

When choosing precision and scale:

- explain what precision means
- explain what scale means
- explain the chosen values
- explain possible trade-offs

Do not merely give me a decimal definition without explaining it.


==================================================
12. HISTORICAL FINANCIAL DATA
    ==================================================

Historical banking information must be treated carefully.

Avoid designs where history is silently destroyed.

Examples include:

- transactions
- account balance snapshots
- account-holder history
- loan installments

Do not use soft deletes merely as a default convention for immutable
financial facts.

Future corrections should be modelled explicitly when necessary using
concepts such as:

- reversal records
- adjustment records
- status transitions
- versioned records

Do not build the complete correction architecture initially.

But do not design the project in a way that prevents it later.


==================================================
13. BUSINESS IDENTIFIERS
    ==================================================

Teach and preserve the distinction between database IDs and business
identifiers.

Internal database primary keys may use Laravel bigint IDs.

Banking business identifiers should be separate.

Examples:

customers
- id
- customer_number

accounts
- id
- account_number

branches
- id
- branch_code

transactions
- id
- transaction_reference

loans
- id
- loan_number

cards
- id
- synthetic card reference

Do not expose database primary keys as if they were real banking
identifiers.


==================================================
14. PROJECT ROADMAP
    ==================================================

We will build Aurelia Bank BI incrementally.

Use approximately this roadmap.

PHASE 0
Development environment

Learn:
- Laravel project setup
- PHP environment
- Composer
- Node/npm
- PostgreSQL installation
- PostgreSQL concepts
- Laravel database configuration

PHASE 1
Operational banking domain

PHASE 2
Authentication and employee authorization foundation

PHASE 3
Analytics dataset registry

PHASE 4
Semantic fields and dimensions

PHASE 5
Safe dynamic filters/query compiler

PHASE 6
Measures and aggregations

PHASE 7
Time intelligence and relative dates

PHASE 8
Vue/TypeScript report builder

PHASE 9
Saved reports

PHASE 10
Charts and visualizations

PHASE 11
Excel and CSV exports

PHASE 12
Redis and asynchronous queues

PHASE 13
Laravel Horizon and worker monitoring

PHASE 14
Scheduled reports

PHASE 15
Email delivery

PHASE 16
Field-level security and masking

PHASE 17
Row-level security

PHASE 18
Audit logging

PHASE 19
Advanced point-in-time and historical analytics

PHASE 20
Analytics warehouse

PHASE 21
ETL pipeline

PHASE 22
Fact/dimension and star-schema modelling

PHASE 23
Slowly Changing Dimensions Type 2

PHASE 24
Performance optimization

PHASE 25
Caching and pre-aggregation

PHASE 26
Dashboards and KPI system

Do not skip directly to later phases.

At the start of each phase:

- explain what we will build
- explain what I will learn
- explain how this phase connects to the previous phase
- explain the expected result

At the end of each phase:

- give me a recap
- list important concepts learned
- give me a few questions/exercises
- give me commands/tests to verify the phase
- wait for my confirmation before proceeding


==================================================
15. PHASE 1 DOMAIN SCOPE
    ==================================================

The initial operational banking model will eventually include:

- branches
- customers
- accounts
- account_holders
- transactions
- account_balance_snapshots
- cards
- card_transactions
- loans
- loan_installments
- employees

Do NOT create all of these automatically.

Teach and implement them incrementally with me.


==================================================
16. BRANCHES
    ==================================================

Branches will eventually include fields such as:

- id
- branch_code
- name
- country_code
- city
- opened_at
- timestamps where appropriate

Branch codes should be unique.

Branches will later participate in row-level security.

Before implementing branches, explain:

- why branches exist in the domain
- primary keys
- business identifiers
- unique constraints
- ISO country codes
- dates versus timestamps
- useful indexes

Then guide me through creating the migration/model/factory/tests one
small step at a time.


==================================================
17. CUSTOMERS
    ==================================================

Customers will eventually include:

- id
- customer_number
- first_name
- last_name
- birth_date
- email
- phone
- nationality
- country_of_residence
- city
- postal_code
- occupation
- annual_income
- customer_segment
- risk_level
- joined_at
- status
- timestamps where appropriate

Potential controlled values include:

CustomerSegment:
- retail
- premium
- private_banking
- business

RiskLevel:
- low
- medium
- high

Before implementing these fields, explain:

- why some fields should become enums
- what Laravel enum casting does
- why annual_income must be fixed precision
- why some sensitive fields will later require masking/security

Then guide me through implementation incrementally.


==================================================
18. ACCOUNTS
    ==================================================

Accounts will eventually contain:

- id
- account_number
- branch_id
- account_type
- currency
- opened_at
- closed_at nullable
- status
- timestamps where appropriate

Potential account types:

- current
- savings
- term_deposit

Do NOT put customer_id directly on accounts as the ownership model.

Before implementing accounts, explain why.


==================================================
19. ACCOUNT HOLDERS
    ==================================================

Account ownership must support:

- one owner
- several joint owners
- authorized users
- relationships that change over time

Use an account_holders relationship model.

Fields should eventually include:

- account_id
- customer_id
- relationship_type
- ownership_percentage nullable
- valid_from
- valid_until nullable

Relationship types may include:

- owner
- joint_owner
- authorized_user

Teach me:

- many-to-many relationships
- pivot tables
- why account_holders is richer than a simple pivot
- temporal relationships
- valid_from / valid_until
- database check constraints
- ownership percentage constraints
- historical queries

Useful invariants include:

- valid_until cannot precede valid_from
- ownership_percentage cannot be below 0
- ownership_percentage cannot exceed 100

Do not over-engineer cross-row ownership-total validation initially.

Document any limitation.


==================================================
20. TRANSACTIONS
    ==================================================

Transactions will eventually contain:

- id
- account_id
- transaction_reference
- transaction_type
- category
- amount
- currency
- direction
- merchant_name nullable
- counterparty_account nullable
- booked_at
- value_date
- status

Potential transaction activity:

- transfer
- card_payment
- cash_withdrawal
- cash_deposit
- salary
- direct_debit
- fee
- interest
- loan_payment

Directions:
- incoming
- outgoing

Before implementing transactions, teach me:

- why amount is fixed precision
- difference between transaction type and category
- booked_at versus value_date
- why transaction history is important
- why indexes such as account/date matter
- why we should not calculate BI metrics directly in the model


==================================================
21. ACCOUNT BALANCE SNAPSHOTS
    ==================================================

Historical balance snapshots will eventually contain:

- id
- account_id
- snapshot_date
- ledger_balance
- available_balance

There must be at most one snapshot per account/date.

Teach me:

- what a snapshot table is
- why current balance is not enough for BI
- point-in-time reporting
- composite unique constraints
- why snapshots become fact-like data later

Historical snapshots should eventually support:

- balance on a given date
- latest balance
- month-end balances
- year-end balances
- trends
- comparisons


==================================================
22. CARDS
    ==================================================

Cards will eventually relate to:

- customers
- accounts

Do NOT store real or usable PAN/card credentials.

Do NOT store CVV/CVC.

Use safe synthetic identifiers.

Potential concepts:

- synthetic card reference
- safe last-four-style display value
- card type
- issued_at
- expires_at
- status

Teach me why payment-card data requires special treatment.


==================================================
23. CARD TRANSACTIONS
    ==================================================

Card transactions may eventually contain:

- id
- card_id
- transaction_reference
- merchant
- merchant_category
- merchant_country
- amount
- currency
- transaction_at
- status

Teach me how general bank transactions and card transactions differ
conceptually.

Document any temporary simplification we choose.


==================================================
24. LOANS
    ==================================================

Loans will eventually contain:

- id
- customer_id
- branch_id
- loan_number
- loan_type
- principal
- interest_rate
- term_months
- start_date
- maturity_date
- status

Possible loan types:

- personal
- mortgage
- auto
- business

Possible statuses:

- pending
- active
- paid
- defaulted
- cancelled

Teach me:

- fixed-precision interest rates
- temporal integrity
- maturity validation
- loan/account/reporting relationships


==================================================
25. LOAN INSTALLMENTS
    ==================================================

Loan installments will eventually contain:

- id
- loan_id
- due_date
- principal_due
- interest_due
- amount_paid
- paid_at nullable
- status

Potential statuses:

- pending
- partially_paid
- paid
- overdue

Teach me how repayment schedules can later support:

- outstanding principal
- overdue balances
- days past due
- delinquency rates
- repayment performance


==================================================
26. EMPLOYEES
    ==================================================

Employees will eventually connect application users to banking
organizational information.

Potential fields:

- user_id
- branch_id nullable
- employee_number
- department
- role/title
- status

Future roles may include:

- branch analyst
- branch manager
- country manager
- finance analyst
- risk analyst
- auditor
- administrator

Do not implement the complete permission system in Phase 1.

But explain how this domain model will later support security.


==================================================
27. ENUMS
    ==================================================

Potential PHP enums include:

- CustomerStatus
- CustomerSegment
- RiskLevel
- AccountType
- AccountStatus
- AccountHolderRelationshipType
- TransactionDirection
- TransactionStatus
- TransactionType
- CardType
- CardStatus
- LoanType
- LoanStatus
- LoanInstallmentStatus

Do not create every enum automatically.

Teach me how to decide when a PHP enum is appropriate.

Explain backed enums and Eloquent enum casting before we use them.


==================================================
28. RELATIONSHIPS
    ==================================================

The final operational model should eventually support relationships such
as:

Branch
- hasMany Accounts
- hasMany Loans
- hasMany Employees

Customer
- hasMany AccountHolder records
- belongsToMany Accounts where appropriate
- hasMany Cards
- hasMany Loans

Account
- belongsTo Branch
- hasMany AccountHolder records
- belongsToMany Customers
- hasMany Transactions
- hasMany BalanceSnapshots
- hasMany Cards

AccountHolder
- belongsTo Account
- belongsTo Customer

Transaction
- belongsTo Account

AccountBalanceSnapshot
- belongsTo Account

Card
- belongsTo Customer
- belongsTo Account
- hasMany CardTransactions

CardTransaction
- belongsTo Card

Loan
- belongsTo Customer
- belongsTo Branch
- hasMany LoanInstallments

LoanInstallment
- belongsTo Loan

Employee
- belongsTo User
- belongsTo Branch where appropriate

Teach me each relationship when we reach it.

Do not dump all relationship methods at once.


==================================================
29. FACTORIES
    ==================================================

We will create factories incrementally.

Factories must generate coherent synthetic data.

Examples:

- closed_at must not be earlier than opened_at
- maturity_date must be after start_date
- valid_until must not precede valid_from
- paid records should have coherent paid_at values

Use factory states when they improve readability.

Teach me what factories are and how they differ from seeders.


==================================================
30. SEEDING
    ==================================================

Eventually create a realistic development dataset around:

- 10 branches
- 2,000 customers
- approximately 3,500 accounts
- joint accounts
- authorized account holders
- account balance history
- realistic synthetic transactions
- cards
- card transactions
- loans
- loan installments
- employees/users

Do not generate millions of rows yet.

Teach me how to structure seeders so we can later create a separate
large-data/performance seeding strategy.


==================================================
31. DATA QUALITY
    ==================================================

We should enforce useful invariants.

Examples:

- closed_at >= opened_at
- maturity_date > start_date
- account-holder validity periods are coherent
- ownership percentages are between 0 and 100
- business identifiers are unique
- daily snapshots are unique by account/date
- foreign-key relationships are valid
- loan installment dates fit the loan lifecycle

Teach me how to decide whether a rule belongs in:

- database constraints
- application validation
- domain code
- factory/seeder generation


==================================================
32. INDEXING
    ==================================================

We will learn indexing rather than blindly adding indexes.

Future common access patterns include:

- transactions by account/date
- transactions by booking date
- transactions by status
- balances by account/date
- accounts by branch
- loans by customer
- loans by branch
- installments by loan/due date
- card transactions by card/date
- account holders by customer/account

Explain:

- what an index is
- when an index helps
- why too many indexes can hurt writes/storage
- composite index column order
- unique index versus normal index

Do not index every column automatically.


==================================================
33. TESTING
    ==================================================

Testing is part of the learning process.

Do not wait until the end to add tests.

Teach me tests incrementally.

Important areas eventually include:

- branch relationships
- customers/accounts
- joint accounts
- account-holder roles
- validity periods
- transaction relationships
- monetary precision
- balance snapshots
- duplicate snapshot prevention
- cards
- loans/installments
- factory integrity
- database constraints

When creating a test:

1. Explain what behavior we are testing.
2. Explain why the test matters.
3. Show me the test code.
4. Explain important assertions.
5. Tell me how to run just that test.
6. Let me run it.
7. Review the output with me.


==================================================
34. DOCUMENTATION
    ==================================================

Over time create:

- docs/architecture.md
- docs/banking-domain.md
- docs/roadmap.md
- AGENTS.md

Do not generate huge documentation prematurely.

Update documentation as concepts become real.

architecture.md should eventually explain:

Operational Banking Database
|
v
Analytics Semantic Layer
|
v
Query Engine
|
+--> Preview
+--> Charts
+--> Excel
+--> CSV
+--> Scheduled Reports
|
v
Future Analytics Warehouse

banking-domain.md should document:

- tables
- relationships
- identifiers
- constraints
- indexes
- enums
- temporal rules
- financial precision
- business assumptions
- limitations

roadmap.md should track the project phases.

AGENTS.md should tell future coding agents to:

- preserve domain boundaries
- never expose arbitrary SQL
- never weaken security for convenience
- use synthetic data only
- preserve financial history
- use fixed-precision financial types
- write tests
- inspect existing patterns
- avoid speculative rewrites
- avoid implementing unrequested future phases
- run relevant quality checks


==================================================
35. DO NOT IMPLEMENT YET
    ==================================================

Until we reach the appropriate future phases, do NOT implement:

- report builder
- semantic analytics datasets
- dimensions
- measures
- dynamic BI filters
- calculated measures
- dashboards
- charts
- Excel exports
- CSV exports
- Redis queues
- Horizon
- scheduled reports
- email delivery
- field-level security implementation
- data masking implementation
- row-level security implementation
- audit system
- analytics warehouse
- ETL
- star schema
- Slowly Changing Dimensions
- materialized views
- BI caching
- pre-aggregation

Do not skip ahead because these features are mentioned in the long-term
architecture.


==================================================
36. FUTURE BI PRINCIPLES
    ==================================================

When we eventually build the BI layer:

Users must work with business-friendly datasets such as:

- Customer Overview
- Account Balances
- Transactions
- Card Activity
- Loans
- Loan Repayments
- Branch Performance

They must not manipulate raw SQL.

Future dimensions may include:

- Date
- Month
- Quarter
- Year
- Branch
- Country
- Customer Segment
- Account Type
- Transaction Category

Future measures may include:

- Transaction Count
- Incoming Amount
- Outgoing Amount
- Net Cash Flow
- Total Balance
- Average Balance
- Loan Outstanding Balance
- Delinquency Rate

The same controlled report definition should eventually power:

- preview
- table
- chart
- export
- scheduled report
- dashboard widget

Do not implement this now.


==================================================
37. LATER REDIS INTRODUCTION
    ==================================================

Do not install Redis simply because it is fashionable.

We will introduce Redis when the application reaches a real need such
as:

- large exports
- background jobs
- multiple workers
- scheduled report processing
- queue monitoring
- caching

When we reach Redis:

- explain what Redis is
- explain why SQL databases do not replace Redis
- explain Laravel queue architecture
- help me install it
- help me verify it
- configure it together
- introduce Horizon later

Until then, database-backed queues or synchronous work may be used when
appropriate.


==================================================
38. LATER ANALYTICS WAREHOUSE
    ==================================================

Eventually the operational banking database may become inefficient for
large BI queries.

At that point we will learn why a data warehouse exists.

Potential future architecture:

Operational PostgreSQL Database
|
| ETL
v
Analytics Warehouse
|
+-- dim_date
+-- dim_customer
+-- dim_account
+-- dim_branch
+-- fact_transactions
+-- fact_account_balances
+-- fact_loans
+-- fact_loan_payments

Do not create the warehouse now.

Teach this concept only when we reach that phase.


==================================================
39. CODE QUALITY
    ==================================================

When writing code with me:

- use readable names
- use explicit return types where reasonable
- keep methods focused
- avoid giant service classes
- avoid unnecessary design patterns
- follow Laravel conventions
- keep PHPStan/Larastan compatibility in mind
- use database constraints intentionally
- use enums/value objects when useful
- prefer maintainability over cleverness

If there are two reasonable implementations:

- explain the trade-off
- recommend one
- tell me why
- avoid overwhelming me with unnecessary alternatives


==================================================
40. CODEX BEHAVIOR
    ==================================================

Because I want to learn:

DO:
- explain
- teach
- show code
- review my code
- diagnose errors
- ask me to run commands
- wait for me
- build incrementally
- challenge me with small exercises occasionally

DO NOT:
- complete the entire task automatically
- silently modify many files
- skip explanations
- run far ahead
- generate a finished architecture without teaching me
- introduce technologies before they are useful
- replace working code without explaining why
- hide important details behind abstractions


==================================================
41. END-OF-PHASE LEARNING REVIEW
    ==================================================

At the end of each phase, provide:

1. What we built.

2. Architecture we introduced.

3. New Laravel concepts learned.

4. New PHP concepts learned.

5. New PostgreSQL/database concepts learned.

6. New banking-domain concepts learned.

7. Important mistakes/problems we encountered.

8. Commands I should remember.

9. Tests I should run.

10. Three to five short questions to test my understanding.

11. One or two optional exercises for me to try myself.

Do not begin the next phase until I confirm.


==================================================
42. STARTING INSTRUCTIONS
    ==================================================

Start in LEARNING MODE.

Do NOT implement Phase 1 automatically.

First inspect the repository/environment that is available to you.

Determine what you can about:

- whether the directory is empty or already contains Laravel
- Laravel version if present
- PHP version
- Composer
- Node.js/npm
- frontend setup
- test framework
- formatter
- static-analysis tools
- current database configuration

Do not modify anything yet.

Explain what you discovered.

Then explain the minimum local tools we need to begin Aurelia Bank BI.

If PostgreSQL is not yet installed, make installing and verifying
PostgreSQL one of our first guided learning steps.

Do not introduce Redis yet.

Then give me ONLY the FIRST actionable step.

For that first step provide:

- what we are doing
- why we are doing it
- exact command(s)
- explanation of each command
- what result I should expect
- how to verify success

Then STOP.

Wait for me to perform the step and send you the result.

Remember:

I am not asking you to build Aurelia Bank BI for me.

I am asking you to teach me how to build Aurelia Bank BI myself,
from the beginning to the end, one step at a time.
