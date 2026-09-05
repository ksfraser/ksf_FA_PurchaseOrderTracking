# FR-PO-001-001: Track PO Lead Time

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Functional Requirement

## FR-PO-001-001: Track PO Lead Time

### Description
Record the actual lead time from PO creation to GRN receipt.

### Input
- PO creation date (from `purch_orders`)
- Expected delivery date (from `purch_orders`)
- Actual receipt date (from `grns`)

### Processing
```
lead_time = received_date - order_date
variance = lead_time - expected_lead_time
```

### Output
- Actual lead time in days
- Variance from expected in days
- On-time flag (variance <= 0)

### Business Rules
- Uses calendar days, not business days
- PO with no GRN = lead time pending
- Cancelled POs excluded from averages

### Acceptance Criteria
- [ ] Lead time calculated on GRN receipt
- [ ] Variance correctly signed (positive = late, negative = early)
- [ ] On-time flag accurate
- [ ] Pending POs tracked separately