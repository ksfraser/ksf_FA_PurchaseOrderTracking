<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\PurchaseOrderTracking;

use Ksfraser\CommonDb\Contract\DbConnectionInterface;

/**
 * Repository for PO tracking data.
 *
 * @since 1.0.0
 */
class PurchaseOrderRepository
{
    /** @var DbConnectionInterface */
    private $db;

    /** @var string */
    private $leadTimeTable;

    /** @var string */
    private $fillRateTable;

    public function __construct(
        DbConnectionInterface $db,
        string $leadTimeTable = '0_ksf_po_lead_times',
        string $fillRateTable = '0_ksf_po_fill_rates'
    ) {
        $this->db = $db;
        $this->leadTimeTable = $leadTimeTable;
        $this->fillRateTable = $fillRateTable;
    }

    public function saveLeadTime(LeadTimeDTO $leadTime): void
    {
        $sql = "INSERT INTO {$this->leadTimeTable}
                (po_number, supplier_id, stock_id, order_date, expected_date, received_date,
                 lead_time_days, expected_lead_time_days, lead_time_variance, quality_rating, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                received_date = VALUES(received_date),
                lead_time_days = VALUES(lead_time_days),
                lead_time_variance = VALUES(lead_time_variance),
                updated_at = CURRENT_TIMESTAMP";

        $this->db->executeUpdate($sql, [
            $leadTime->getPoNumber(),
            $leadTime->getSupplierId(),
            $leadTime->getStockId(),
            $leadTime->orderDate->format('Y-m-d'),
            $leadTime->expectedDate !== null ? $leadTime->expectedDate->format('Y-m-d') : null,
            $leadTime->receivedDate !== null ? $leadTime->receivedDate->format('Y-m-d') : null,
            $leadTime->leadTimeDays,
            $leadTime->expectedLeadTimeDays,
            $leadTime->leadTimeVariance,
            $leadTime->qualityRating,
            $leadTime->notes,
        ]);
    }

    public function saveFillRate(FillRateDTO $fillRate): void
    {
        $sql = "INSERT INTO {$this->fillRateTable}
                (po_number, po_line, supplier_id, stock_id, qty_ordered, qty_received,
                 qty_invoiced, qty_cancelled, fill_rate, fulfillment_status,
                 first_received_date, last_received_date, expected_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                qty_received = VALUES(qty_received),
                fill_rate = VALUES(fill_rate),
                fulfillment_status = VALUES(fulfillment_status),
                last_received_date = VALUES(last_received_date),
                updated_at = CURRENT_TIMESTAMP";

        $this->db->executeUpdate($sql, [
            $fillRate->poNumber,
            $fillRate->poLine,
            $fillRate->supplierId,
            $fillRate->stockId,
            $fillRate->qtyOrdered,
            $fillRate->qtyReceived,
            $fillRate->qtyInvoiced,
            $fillRate->qtyCancelled,
            $fillRate->fillRate,
            $fillRate->fulfillmentStatus,
            $fillRate->firstReceivedDate !== null ? $fillRate->firstReceivedDate->format('Y-m-d') : null,
            $fillRate->lastReceivedDate !== null ? $fillRate->lastReceivedDate->format('Y-m-d') : null,
            $fillRate->expectedDate !== null ? $fillRate->expectedDate->format('Y-m-d') : null,
        ]);
    }

    public function getAverageLeadTime(int $supplierId): ?float
    {
        $sql = "SELECT AVG(lead_time_days) as avg_days
                FROM {$this->leadTimeTable}
                WHERE supplier_id = ? AND lead_time_days IS NOT NULL
                AND received_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";

        $result = $this->db->fetchAssoc($sql, [$supplierId]);
        return $result ? (float) $result['avg_days'] : null;
    }

    public function getAverageFillRate(int $supplierId): ?float
    {
        $sql = "SELECT AVG(fill_rate) as avg_fill
                FROM {$this->fillRateTable}
                WHERE supplier_id = ? AND fill_rate > 0
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";

        $result = $this->db->fetchAssoc($sql, [$supplierId]);
        return $result ? (float) $result['avg_fill'] : null;
    }

    public function getPendingPoLines(int $supplierId): array
    {
        $sql = "SELECT fr.*, po.ord_date as order_date
                FROM {$this->fillRateTable} fr
                INNER JOIN purch_orders po ON fr.po_number = po.order_no
                WHERE fr.supplier_id = ? AND fr.fulfillment_status IN ('pending', 'partial')
                ORDER BY po.ord_date ASC";

        $rows = $this->db->fetchAll($sql, [$supplierId]);
        return array_map(fn($row) => FillRateDTO::fromArray($row), $rows);
    }

    public function getCachedMetrics(): array
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'supplier_metrics' => [],
        ];
    }
}