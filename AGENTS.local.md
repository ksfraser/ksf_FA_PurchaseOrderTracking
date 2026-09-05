<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.local.md — ksf_FA_PurchaseOrderTracking
## Purpose
Track purchase order lead times and fill rates to optimize reorder timing and supplier performance.

## Hook Communication
```
[GRN Received] → grn_received → PurchaseOrderTracking → po_tracking_data → [Others]
```

## Dependencies
- ksf_common_db (DbConnectionInterface)
- FA purch_orders, grns tables (read only)

## Development Workflow
All development is done in the **devel tree** (`~/Documents/ksf_FA_PurchaseOrderTracking`). Do **not** edit files in the Infrastructure bind point directly.

### Workflow Steps
1. **Develop** in this repo (feature/fix branches)
2. **Test**: `composer install && ./vendor/bin/phpunit`
3. **Lint**: `php -l` on modified PHP files
4. **Commit** and **Push** to GitHub
5. **Deploy** to Infrastructure:
   ```
   rsync -av --exclude='.git' ~/Documents/ksf_FA_PurchaseOrderTracking/ ~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_PurchaseOrderTracking/
   ```

### Infrastructure Bind Point
| Path | Purpose |
|------|---------|
| `~/Documents/ksf_FA_PurchaseOrderTracking` | Devel tree |
| `~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_PurchaseOrderTracking` | Deployment target |