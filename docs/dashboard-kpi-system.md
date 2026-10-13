# Aurelia Dashboard and KPI System

## Purpose

The dashboard system allows authorized employees to compose reusable
analytics views from governed saved reports.

Dashboards and widgets never contain arbitrary SQL.

Every widget is executed through the existing semantic query,
field-authorization, row-scope, warehouse-routing, audit, and caching
layers.

## Existing system dashboard

The current transaction overview remains the system dashboard.

It provides predefined transaction summary, daily cash-flow, and
transaction-mix widgets.

The system dashboard is application-defined and is not stored as an
employee-owned dashboard.

## Employee dashboards

An employee dashboard is a persisted collection of governed widgets.

Initial dashboard attributes:

- owner employee
- name
- optional description
- default-dashboard indicator
- timestamps

A dashboard belongs to exactly one employee.

Employees may view and modify only dashboards allowed by dashboard
authorization policy.

An employee may have at most one default dashboard.

## Dashboard widgets

A widget belongs to one dashboard and references one saved report.

Initial widget attributes:

- dashboard
- saved report
- title
- widget type
- grid column
- grid row
- grid width
- grid height
- optional configuration
- timestamps

Supported initial widget types:

- KPI
- chart
- table

Widget configuration must be a controlled JSON object validated by the
application. It must never contain SQL, database identifiers, class
names, or executable expressions.

## Saved-report reuse

A widget references a saved report rather than copying its report
definition.

This preserves one governed definition for preview, charts, exports,
scheduled reports, and dashboards.

The dashboard owner must be authorized to use the referenced saved
report.

The saved report must be reauthorized whenever the dashboard is viewed.
Previously valid access must not grant permanent access after an
employee role, branch, dataset permission, or field permission changes.

A missing, deleted, or inaccessible saved report makes the widget
unavailable. It must not weaken authorization or expose cached data.

## KPI rules

A KPI represents one controlled measure.

A KPI must resolve to a scalar value or to explicitly separated values
such as one value per currency.

Currency-aware measures must retain currency context. Values in
different currencies must never be silently combined.

A KPI may optionally compare its current value with the equivalent
previous reporting period.

Comparison values must use the existing governed time-intelligence
query path.

Percentage change must not be calculated when the previous value is
zero. That state must be represented explicitly rather than as
infinity or an invented percentage.

## Chart rules

A chart widget uses the saved report's validated visualization
configuration.

Initially supported chart types remain:

- line
- bar

Chart dimensions, measures, and series must continue to reference
semantic fields present in the saved report definition.

## Table rules

A table widget displays governed query rows from its saved report.

Result limits remain server-controlled.

A dashboard table is not a replacement for the export system.

## Layout rules

The initial dashboard uses a twelve-column, zero-based grid.

Layout rules:

- column is between 0 and 11
- row is zero or greater
- width and height are between 1 and 12
- column plus width cannot exceed 12
- overlapping widgets are initially allowed
- widgets are rendered in document order

The server validates layout values independently from the browser.

Automatic collision resolution is not part of the first implementation.

## Ownership and authorization

Dashboard ownership is based on employee identity, not user email or
browser state.

Authorization applies independently to:

- viewing the dashboard
- updating dashboard metadata
- deleting the dashboard
- adding widgets
- updating widgets
- deleting widgets
- executing each referenced saved report

User-selected filters and widget configuration can never override row
scope or field security.

## Caching

Dashboard result caches must include:

- employee authorization context
- branch scope
- role
- reporting timezone
- reporting period
- dashboard identity
- dashboard update version or timestamp
- referenced saved-report update state
- aggregate cache generation

Updating a dashboard or widget must prevent reuse of an older dashboard
result.

Cached data must never be shared across incompatible authorization
contexts.

## Auditing

Important dashboard actions will be audited using safe operational
metadata.

Candidate actions:

- dashboard created
- dashboard viewed
- dashboard updated
- dashboard deleted
- dashboard widget created
- dashboard widget updated
- dashboard widget deleted

Audit context may contain identifiers, widget type, widget count,
period, cache-hit state, and duration.

Audit context must not contain result rows, banking values, report
filters, SQL, or exception messages.

## Failure behaviour

One inaccessible widget must not expose data.

The initial implementation may represent an inaccessible widget as
unavailable while continuing to render other authorized widgets.

Database and infrastructure failures must not be disguised as
authorization failures.

## Initial limitations

The first implementation will not include:

- shared dashboards
- public dashboards
- cross-employee dashboard editing
- arbitrary custom SQL
- arbitrary calculated KPI formulas
- drag-and-drop collision resolution
- real-time collaborative editing
- dashboard templates
- external embedding
