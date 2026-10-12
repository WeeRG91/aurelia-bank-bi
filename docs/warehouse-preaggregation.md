# Aurelia Warehouse Pre-aggregation

## Purpose

Phase 25 introduces governed pre-aggregated warehouse data for common
dashboard workloads.

Pre-aggregation must reduce query work without changing:

- financial results
- row-level authorization
- field-level authorization
- reporting-timezone behavior
- Slowly Changing Dimension history
- semantic dataset behavior

Redis result caching and warehouse pre-aggregation solve different
problems.

Redis avoids executing an identical recently cached request.

Warehouse pre-aggregation reduces the cost of executing an eligible
query when no cached result exists.

## Initial aggregate

The first aggregate is:

- `warehouse.agg_daily_transaction_activity`

It supports the existing transaction dashboard widgets:

- transaction summary
- daily cash flow
- transaction mix

## Grain

Exactly one aggregate row represents transaction activity for one:

- reporting timezone
- local reporting date
- historical branch version
- currency
- transaction type

The unique grain is therefore:

```text
reporting_timezone
+ reporting_date_key
+ branch_key
+ currency
+ transaction_type

## Measured validation baseline

The initial validation used the seeded warehouse dataset:

- 900 transaction facts
- 865 aggregate rows
- Europe/Luxembourg reporting timezone
- branch analyst restricted to source branch 2
- reporting period from 2026-09-01 through 2026-09-30
- dimensions: booking date, branch, and currency
- measures: transaction count and net cash flow

The operational, detailed warehouse, and daily aggregate results were
normalized and compared. All returned the same six result groups and
identical values.

The authorization result contained only branch `LUX-ESCH`.

An unsupported query containing the `account_type` dimension was routed
back to `warehouse.fact_transactions`.

### PostgreSQL execution plans

Detailed fact query:

- planner cost: 59.85
- planning time: 0.505 ms
- execution time: 0.408 ms
- shared buffer hits: 12
- 300 branch facts inspected
- 8 facts retained by the reporting-date filter

Daily aggregate query:

- planner cost: 37.02
- planning time: 0.475 ms
- execution time: 0.385 ms
- shared buffer hits: 65
- 865 aggregate rows inspected
- 24 rows retained by the date join
- 8 rows retained after branch authorization

The aggregate execution time was approximately 5.6 percent lower, but
its buffer activity was higher.

This small improvement is expected for the demonstration dataset because
900 facts produce 865 daily aggregate groups, a reduction of only about
3.9 percent.

The primary benefit appears as transaction volume grows and many facts
share the same reporting date, historical branch, currency, and
transaction type. PostgreSQL may also choose the aggregate indexes
differently once table statistics and cardinality justify them.

The baseline confirms semantic parity and authorization safety. It does
not claim a production-scale performance result from the small seeded
dataset.
