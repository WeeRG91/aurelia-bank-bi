# Aurelia Warehouse Slowly Changing Dimensions

## Purpose

The warehouse preserves historical versions of branch and account
attributes using Slowly Changing Dimension Type 2.

A changed business entity creates a new dimension row instead of
overwriting its previous warehouse row.

## Scope

Type 2 history initially applies to:

- `warehouse.dim_branch`
- `warehouse.dim_account`

The date dimension is static and does not require versioning.

Transaction facts retain relationships to the dimension versions that
were effective for their business event.

## Natural and surrogate keys

The stable operational identifiers are:

- `dim_branch.source_branch_id`
- `dim_account.source_account_id`

Each historical version receives a different warehouse surrogate key:

- `dim_branch.branch_key`
- `dim_account.account_key`

Operational identifiers identify the business entity.

Surrogate keys identify one historical version of that entity.

## Version columns

Versioned dimensions contain:

- `version`
- `valid_from`
- `valid_to`
- `is_current`
- `attribute_hash`

`version` starts at `1` and increases by one for each changed version.

`valid_from` is inclusive.

`valid_to` is exclusive.

A current version has:

- `valid_to = null`
- `is_current = true`

A historical version has:

- a non-null `valid_to`
- `is_current = false`

The validity interval is represented as:

```text
[valid_from, valid_to)
