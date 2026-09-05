<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\PurchaseOrderTracking;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles PO tracking calculations and GRN recording.
 *
 * @since 1.0.0
 */
class PurchaseOrderTrackingHandler
{
    private const CRON_TYPE = 'nightly_recalc';

    /** @var PurchaseOrderRepository */
    private $repository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PurchaseOrderRepository $repository,
        ?LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
    }

    public function performNightlyRecalculation(): void
    {
        $this->logger->info('Starting nightly PO tracking recalculation');
        $this->updateSupplierMetrics();
    }

    private function updateSupplierMetrics(): void
    {
        $sql = "SELECT supplier_id FROM purch_orders WHERE ord_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) GROUP BY supplier_id";
        $suppliers = $this->db->fetchAll($sql, []);

        foreach ($suppliers as $row) {
            $supplierId = (int) $row['supplier_id'];
            $avgLeadTime = $this->repository->getAverageLeadTime($supplierId);
            $avgFillRate = $this->repository->getAverageFillRate($supplierId);

            $this->logger->debug('Supplier metrics', [
                'supplier_id' => $supplierId,
                'avg_lead_time' => $avgLeadTime,
                'avg_fill_rate' => $avgFillRate,
            ]);
        }
    }

    public function recordGrnReceipt(array $data): void
    {
        $poNumber = $data['po_number'] ?? '';
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $lines = $data['lines'] ?? [];
        $receivedDate = isset($data['received_date'])
            ? new \DateTimeImmutable($data['received_date'])
            : new \DateTimeImmutable();

        if (empty($poNumber) || $supplierId <= 0) {
            return;
        }

        $this->logger->info('Recording GRN receipt', [
            'po_number' => $poNumber,
            'lines' => count($lines),
        ]);

        $this->recordLeadTime($poNumber, $supplierId, $receivedDate);

        foreach ($lines as $line) {
            $this->recordFillRate($poNumber, $supplierId, $line, $receivedDate);
        }
    }

    private function recordLeadTime(string $poNumber, int $supplierId, \DateTimeImmutable $receivedDate): void
    {
        $sql = "SELECT ord_date, delivery_date FROM purch_orders WHERE order_no = ?";
        $po = $this->db->fetchAssoc($sql, [$poNumber]);

        if (!$po) {
            return;
        }

        $orderDate = new \DateTimeImmutable($po['ord_date']);
        $expectedDate = isset($po['delivery_date'])
            ? new \DateTimeImmutable($po['delivery_date'])
            : null;

        $leadTime = new LeadTimeDTO($poNumber, $supplierId, $orderDate);
        $leadTime->expectedDate = $expectedDate;
        $leadTime->receivedDate = $receivedDate;
        $leadTime->calculateLeadTime();

        $this->repository->saveLeadTime($leadTime);
    }

    private function recordFillRate(
        string $poNumber,
        int $supplierId,
        array $line,
        \DateTimeImmutable $receivedDate
    ): void {
        $stockId = $line['stock_id'] ?? '';
        $poLine = (int) ($line['po_line'] ?? 1);
        $qtyOrdered = (float) ($line['qty_ordered'] ?? 0);
        $qtyReceived = (float) ($line['qty_received'] ?? 0);

        if (empty($stockId) || $qtyOrdered <= 0) {
            return;
        }

        $fillRate = new FillRateDTO($poNumber, $poLine, $supplierId, $stockId, $qtyOrdered);

        if ($qtyReceived > 0) {
            $fillRate->addReceivedQty($qtyReceived, $receivedDate);
        }

        $this->repository->saveFillRate($fillRate);
    }

    public function recordPoCreation(array $data): void
    {
        $this->logger->info('PO created', [
            'po_number' => $data['po_number'] ?? '',
            'supplier_id' => $data['supplier_id'] ?? 0,
        ]);
    }

    public function recordPoModification(array $data): void
    {
        $this->logger->info('PO modified', [
            'po_number' => $data['po_number'] ?? '',
        ]);
    }

    public function getCachedMetrics(): array
    {
        return $this->repository->getCachedMetrics();
    }
}