# Aurelia Analytics Warehouse Star Schema

## Transaction fact grain

Exactly one row in `warehouse.fact_transactions` represents one
operational banking transaction identified by `source_transaction_id`.

The fact row represents the latest successfully staged state of that
transaction.

This grain must never be changed implicitly by joins to customers,
account holders, cards, or other one-to-many relationships.

## Source

The dimensional model is populated from:

- `warehouse.stg_transactions`

It does not query operational `public` tables directly.

## Dimensions

### dim_date

One row represents one calendar date.

Natural key:

- `calendar_date`

Warehouse key:

- `date_key`, encoded as `YYYYMMDD`

Important attributes:

- calendar date
- year
- quarter
- month
- month name
- ISO week
- day of month
- ISO weekday
- weekend indicator
- ISO year

`fact_transactions` uses this dimension twice:

- `booking_date_key`
- `value_date_key`

This is called a role-playing dimension.

### dim_branch

One row initially represents the current warehouse version of one
operational branch.

Natural source identifier:

- `source_branch_id`

Surrogate warehouse key:

- `branch_key`

Attributes:

- branch code
- branch name
- country code
- city

Historical branch versions will be introduced separately during the
Slowly Changing Dimensions phase.

### dim_account

One row initially represents the current warehouse version of one
operational account.

Natural source identifier:

- `source_account_id`

Surrogate warehouse key:

- `account_key`

Attributes:

- account number
- account type
- currency
- opened date
- closed date
- status

`account_number` remains a confidential identifier and must continue to
use field-level authorization.

Historical account versions will be introduced separately during the
Slowly Changing Dimensions phase.

## Transaction fact

### fact_transactions

Grain:

- one row per `source_transaction_id`

Keys:

- `transaction_fact_key`
- `source_transaction_id`
- `account_key`
- `branch_key`
- nullable `booking_date_key`
- nullable `value_date_key`

Degenerate identifiers and descriptive attributes:

- transaction reference
- transaction type
- category
- currency
- direction
- transaction status

Measures:

- absolute amount
- signed amount

Signed amount rule:

- incoming transaction: positive amount
- outgoing transaction: negative amount

A transaction count does not need to be stored because each fact row
represents exactly one transaction.

## Null rules

`account_key` and `branch_key` are required.

`booking_date_key` and `value_date_key` may be null for transactions
that have not yet been booked.

A null booking date means “not booked yet”; it does not mean an unknown
calendar date.

## Additivity rules

Transaction count is fully additive.

Absolute and signed amounts are additive across transactions, dates,
accounts, and branches only when currency is preserved in the grouping.

Amounts in different currencies must not be summed together unless an
explicit currency-conversion process has been applied.

## Key strategy

Operational identifiers are retained for lineage and idempotent loads.

Surrogate warehouse keys are used for dimension relationships.

Facts reference warehouse surrogate keys rather than operational
primary keys.

This separation allows dimension history to be introduced later
without changing operational identifiers.

## Security

The warehouse does not bypass application authorization.

The following remain confidential:

- transaction reference
- account number

Row-level branch restrictions and field-level permissions continue to
apply when analytical queries are redirected to the star schema.

## Initial loading strategy

Phase 22 loads the star schema from `warehouse.stg_transactions`.

Dimension rows are upserted before fact rows.

Fact rows are then upserted by `source_transaction_id`.

The load is transactional and idempotent.

Phase 23 will extend applicable dimensions with Slowly Changing
Dimension Type 2 history.
