# BR-PO-001: Purchase Order Tracking Module

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Business Requirement

Track purchase order lead times and fill rates to optimize reorder timing and supplier performance. Records PO→GRN duration and fulfillment completeness.

### Problem Statement

Without PO tracking:
1. No visibility into supplier lead time performance
2. Can't calculate if orders will arrive before stockout
3. No fill rate visibility per supplier/per item

### Solution

- Record PO creation date and expected delivery
- On GRN receipt: calculate actual lead time
- Track ordered vs received quantities (fill rate)
- Nightly recalculation of supplier averages
- Broadcast `po_tracking_data` hook

### Scope

**In Scope:**
- Listen to `grn_received` hook for actual receipt
- Calculate lead time variance (on-time vs late)
- Track fill rate per PO line
- Supplier performance metrics
- Cron recalculation of averages

**Out of Scope:**
- Duplicating FA `purch_orders` data
- Modifying PO workflow

### Dependencies

- FA `purch_orders` table (read only)
- FA `grns` table (read only)
- `ksf_common_db` for DB abstraction