<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\PurchaseOrderTracking;

/**
 * Data transfer object for PO fill rate.
 *
 * @since 1.0.0
 */
class FillRateDTO
{
    /** @var int|null */
    private $id;

    /** @var string */
    private $poNumber;

    /** @var int */
    private $poLine;

    /** @var int */
    private $supplierId;

    /** @var string */
    private $stockId;

    /** @var float */
    private $qtyOrdered;

    /** @var float */
    private $qtyReceived;

    /** @var float */
    private $qtyInvoiced;

    /** @var float */
    private $qtyCancelled;

    /** @var float */
    private $fillRate;

    /** @var string */
    private $fulfillmentStatus;

    /** @var \DateTimeImmutable|null */
    private $firstReceivedDate;

    /** @var \DateTimeImmutable|null */
    private $lastReceivedDate;

    /** @var \DateTimeImmutable|null */
    private $expectedDate;

    public function __construct(
        string $poNumber,
        int $poLine,
        int $supplierId,
        string $stockId,
        float $qtyOrdered
    ) {
        $this->poNumber = $poNumber;
        $this->poLine = $poLine;
        $this->supplierId = $supplierId;
        $this->stockId = $stockId;
        $this->qtyOrdered = $qtyOrdered;
        $this->qtyReceived = 0;
        $this->qtyInvoiced = 0;
        $this->qtyCancelled = 0;
        $this->fillRate = 0;
        $this->fulfillmentStatus = 'pending';
    }

    public static function fromArray(array $data): self
    {
        $dto = new self(
            $data['po_number'],
            (int) ($data['po_line'] ?? 1),
            (int) $data['supplier_id'],
            $data['stock_id'],
            (float) $data['qty_ordered']
        );
        $dto->id = isset($data['id']) ? (int) $data['id'] : null;
        $dto->qtyReceived = (float) ($data['qty_received'] ?? 0);
        $dto->qtyInvoiced = (float) ($data['qty_invoiced'] ?? 0);
        $dto->qtyCancelled = (float) ($data['qty_cancelled'] ?? 0);
        $dto->fillRate = (float) ($data['fill_rate'] ?? 0);
        $dto->fulfillmentStatus = $data['fulfillment_status'] ?? 'pending';
        $dto->firstReceivedDate = isset($data['first_received_date'])
            ? new \DateTimeImmutable($data['first_received_date']) : null;
        $dto->lastReceivedDate = isset($data['last_received_date'])
            ? new \DateTimeImmutable($data['last_received_date']) : null;
        $dto->expectedDate = isset($data['expected_date'])
            ? new \DateTimeImmutable($data['expected_date']) : null;
        return $dto;
    }

    public function addReceivedQty(float $qty, \DateTimeImmutable $date): void
    {
        $this->qtyReceived += $qty;

        if ($this->firstReceivedDate === null) {
            $this->firstReceivedDate = $date;
        }
        $this->lastReceivedDate = $date;

        $this->recalculateFillRate();
    }

    public function recalculateFillRate(): void
    {
        if ($this->qtyOrdered > 0) {
            $this->fillRate = ($this->qtyReceived / $this->qtyOrdered) * 100;
        }

        $remaining = $this->qtyOrdered - $this->qtyReceived - $this->qtyCancelled;

        if ($remaining <= 0) {
            $this->fulfillmentStatus = $this->qtyReceived > $this->qtyOrdered ? 'over_shipped' : 'complete';
        } elseif ($this->qtyReceived > 0) {
            $this->fulfillmentStatus = 'partial';
        }
    }

    public function getFillRate(): float
    {
        return $this->fillRate;
    }

    public function isFullyFulfilled(): bool
    {
        return $this->fulfillmentStatus === 'complete';
    }

    public function getShortfall(): float
    {
        return max(0, $this->qtyOrdered - $this->qtyReceived - $this->qtyCancelled);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->poNumber,
            'po_line' => $this->poLine,
            'supplier_id' => $this->supplierId,
            'stock_id' => $this->stockId,
            'qty_ordered' => $this->qtyOrdered,
            'qty_received' => $this->qtyReceived,
            'qty_invoiced' => $this->qtyInvoiced,
            'qty_cancelled' => $this->qtyCancelled,
            'fill_rate' => $this->fillRate,
            'fulfillment_status' => $this->fulfillmentStatus,
            'first_received_date' => $this->firstReceivedDate !== null ? $this->firstReceivedDate->format('Y-m-d') : null,
            'last_received_date' => $this->lastReceivedDate !== null ? $this->lastReceivedDate->format('Y-m-d') : null,
            'expected_date' => $this->expectedDate !== null ? $this->expectedDate->format('Y-m-d') : null,
        ];
    }
}