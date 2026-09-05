# FR-PO-001-002: Calculate Fill Rate

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Functional Requirement

## FR-PO-001-002: Calculate Fill Rate

### Description
Track the percentage of ordered quantity received versus ordered.

### Input
- Ordered quantity per PO line (from `purch_order_details`)
- Received quantity per PO line (from `grn_items`)

### Processing
```
fill_rate = (received_qty / ordered_qty) * 100
```

### Output
- Fill rate percentage per PO line
- Shortfall quantity

### Business Rules
- Over-shipments capped at 100% fill rate
- Multiple GRNs accumulate toward fill rate
- Partially filled lines tracked until PO closed

### Acceptance Criteria
- [ ] Fill rate calculated correctly for partial receipts
- [ ] Fill rate capped at 100% for over-shipments
- [ ] Shortfall correctly calculated
- [ ] Multiple receipts accumulate correctly