# Aurelia Warehouse Performance Baseline

## Purpose

This document records representative analytics-query performance before
Phase 24 optimizations.

Performance changes must preserve:

- financial results
- row-level authorization
- field-level authorization
- dataset semantics
- historical dimension resolution

## Environment

- Laravel 13
- PHP 8.5
- PostgreSQL 18
- local Windows development environment
- warm PostgreSQL buffer cache

## Dataset size

At the time of measurement:

- operational transactions: 900
- warehouse transaction facts: 900
- warehouse branches: 3
- warehouse accounts: 12

This dataset is intentionally small. PostgreSQL may correctly prefer
sequential scans at this size.

## Representative workload

Business question:

> Return transaction count and net cash flow grouped by branch and
> currency for August and September 2026.

The operational query uses:

- `transactions`
- `accounts`
- `branches`
- a runtime `CASE` expression for signed amounts

The warehouse query uses:

- `warehouse.fact_transactions`
- `warehouse.dim_branch`
- the precomputed `signed_amount` measure

## Baseline results

| Metric | Operational | Warehouse |
|---|---:|---:|
| Planning time | 5.567 ms | 6.032 ms |
| Execution time | 2.343 ms | 0.394 ms |
| Input rows | 396 | 396 |
| Output groups | 9 | 9 |
| Aggregate memory | 48 kB | 32 kB |
| Main joins | 2 | 1 |

The warehouse execution was approximately six times faster during this
local measurement.

This ratio must not be treated as a production guarantee because the
dataset is small and the cache was warm.

## Plan observations

Both queries used sequential scans.

This is reasonable for 900 rows because reading the table sequentially
can cost less than traversing an index.

The operational query required:

- a sequential transaction scan
- repeated account lookups
- memoization of account keys
- a branch hash join
- runtime signed-amount calculation

The warehouse query required:

- a sequential fact scan
- one branch hash join
- aggregation of precomputed signed amounts

## Correctness result

The normalized result sets from both queries were identical.

Performance changes are acceptable only when this equivalence continues
to hold.

## Optimization rules

1. Measure before changing indexes or queries.
2. Preserve financial precision.
3. Do not optimize by weakening authorization.
4. Do not assume a sequential scan is inherently bad.
5. Compare estimated rows with actual rows.
6. Consider buffer usage as well as execution time.
7. Re-run equivalent queries after every material change.
8. Avoid brittle automated assertions against exact execution times.

## Semantic join-pruning result

The production warehouse dataset source originally declared all three
dimension joins for every transaction query:

- `dim_account`
- `dim_branch`
- `dim_date`

A representative branch-authorized query selected:

- branch
- currency
- transaction count
- net cash flow

Only `dim_branch` was required by the selected fields and row-level
authorization.

PostgreSQL automatically removed the unused left join to `dim_date`,
but retained the unused inner join to `dim_account`.

Dependency-aware join pruning was therefore added to the semantic query
compiler. The compiler now considers:

- selected dimensions
- selected measures
- directional measure columns
- filter dimensions
- row-level authorization columns
- transitive join dependencies

The optimized query retained only:

```sql
INNER JOIN dim_branch
    ON fact_transactions.branch_key = dim_branch.branch_key
