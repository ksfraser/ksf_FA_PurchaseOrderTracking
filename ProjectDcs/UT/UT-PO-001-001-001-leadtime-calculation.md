# UT-PO-001-001-001: LeadTimeDTO Calculation

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Unit Test

## UT-PO-001-001-001: LeadTimeDTO Calculation

### Class Under Test
`Ksfraser\FrontAccounting\PurchaseOrderTracking\LeadTimeDTO`

### Method
`calculateLeadTime()`

### Test Case
Verify lead time and variance calculation.

### Test Data
```php
order_date = '2026-09-01'
expected_date = '2026-09-08' (7 days)
received_date = '2026-09-10' (9 days)
```

### Expected Result
- Lead time = 9 days
- Variance = +1 day (late)

### Related FR
FR-PO-001-001