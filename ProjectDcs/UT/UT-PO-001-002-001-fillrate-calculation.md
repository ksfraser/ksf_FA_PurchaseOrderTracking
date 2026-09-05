# UT-PO-001-002-001: FillRateDTO Calculation

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Unit Test

## UT-PO-001-002-001: FillRateDTO Calculation

### Class Under Test
`Ksfraser\FrontAccounting\PurchaseOrderTracking\FillRateDTO`

### Method
`recalculateFillRate()`

### Test Case
Verify fill rate calculation for partial fulfillment.

### Test Data
```php
ordered_qty = 100.0
received_qty = 75.0
```

### Expected Result
- Fill rate = 75.0%
- Shortfall = 25.0
- Fully fulfilled = false

### Related FR
FR-PO-001-002