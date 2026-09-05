# UC-PO-001: Track PO Lead Time

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Use Case

## UC-PO-001: Track PO Lead Time

### Primary Actor
Inventory Manager / Purchasing Agent

### Goal
Monitor supplier performance and improve delivery timing

### Trigger
GRN received or nightly cron

### Main Flow

**GRN Receipt Path:**
1. Receiving module broadcasts `grn_received` hook
2. PO Tracking records lead time (order_date → received_date)
3. Calculates variance from expected date
4. Updates fill rate per line item

**Cron Path:**
1. Cron invokes `nightly_recalc`
2. Recalculates supplier average lead times
3. Updates performance metrics

### Related FR
FR-PO-001-001