-- PO Lead Time Tracking table
CREATE TABLE IF NOT EXISTS `0_ksf_po_lead_times` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(20) NOT NULL,
    `supplier_id` INT NOT NULL,
    `stock_id` VARCHAR(20) NULL COMMENT 'NULL for header-level PO',
    `order_date` DATE NOT NULL,
    `expected_date` DATE NULL,
    `received_date` DATE NULL COMMENT 'Actual GRN date',
    `lead_time_days` INT NULL COMMENT 'received_date - order_date',
    `expected_lead_time_days` INT NULL COMMENT 'expected_date - order_date',
    `lead_time_variance` INT NULL COMMENT 'actual - expected (negative = early)',
    `quality_rating` ENUM('acceptable', 'minor_issues', 'major_issues', 'rejected') NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_stock` (`stock_id`),
    INDEX `idx_order_date` (`order_date`),
    INDEX `idx_lead_time` (`lead_time_days`),
    CONSTRAINT `fk_po_leadtime_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `0_suppliers` (`supplier_id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- PO Fill Rate Tracking table
CREATE TABLE IF NOT EXISTS `0_ksf_po_fill_rates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(20) NOT NULL,
    `po_line` INT NOT NULL DEFAULT 1,
    `supplier_id` INT NOT NULL,
    `stock_id` VARCHAR(20) NOT NULL,
    `qty_ordered` DECIMAL(15,4) NOT NULL,
    `qty_received` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `qty_invoiced` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `qty_cancelled` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `fill_rate` DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'qty_received / qty_ordered * 100',
    `fulfillment_status` ENUM('pending', 'partial', 'complete', 'over_shipped', 'cancelled') NOT NULL DEFAULT 'pending',
    `first_received_date` DATE NULL,
    `last_received_date` DATE NULL,
    `expected_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_po_number` (`po_number`),
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_stock` (`stock_id`),
    INDEX `idx_fill_rate` (`fill_rate`),
    UNIQUE KEY `uk_po_line` (`po_number`, `po_line`)
) ENGINE=InnoDB;

-- PO Events processed log (for hook tracking, not duplicating FA purch_orders)
CREATE TABLE IF NOT EXISTS `0_ksf_po_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(20) NOT NULL,
    `event_type` ENUM('created', 'modified', 'received', 'closed', 'cancelled') NOT NULL,
    `supplier_id` INT NOT NULL,
    `event_date` DATE NOT NULL,
    `lines_total` INT DEFAULT 0,
    `lines_received` INT DEFAULT 0,
    `processed` TINYINT(1) NOT NULL DEFAULT 0,
    `processed_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_unprocessed` (`processed`, `created_at`),
    INDEX `idx_po_event` (`po_number`, `event_type`)
) ENGINE=InnoDB;

-- Cron processing checkpoint (avoids re-querying FA tables)
CREATE TABLE IF NOT EXISTS `0_ksf_po_tracking_cron` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cron_type` VARCHAR(30) NOT NULL COMMENT 'nightly_recalc, etc',
    `last_run_at` DATETIME NOT NULL,
    `last_po_processed` VARCHAR(20) NOT NULL DEFAULT '',
    `records_processed` INT NOT NULL DEFAULT 0,
    `next_scheduled_run` DATETIME NULL,
    `status` ENUM('idle', 'running', 'completed', 'failed') NOT NULL DEFAULT 'idle',
    `error_message` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cron_type` (`cron_type`)
) ENGINE=InnoDB;