<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\PurchaseOrderTracking;

/**
 * Data transfer object for PO lead time record.
 *
 * @since 1.0.0
 */
class LeadTimeDTO
{
    /** @var int|null */
    private $id;

    /** @var string */
    private $poNumber;

    /** @var int */
    private $supplierId;

    /** @var string|null */
    private $stockId;

    /** @var \DateTimeImmutable */
    private $orderDate;

    /** @var \DateTimeImmutable|null */
    private $expectedDate;

    /** @var \DateTimeImmutable|null */
    private $receivedDate;

    /** @var int|null */
    private $leadTimeDays;

    /** @var int|null */
    private $expectedLeadTimeDays;

    /** @var int|null */
    private $leadTimeVariance;

    /** @var string|null */
    private $qualityRating;

    /** @var string|null */
    private $notes;

    public function __construct(
        string $poNumber,
        int $supplierId,
        \DateTimeImmutable $orderDate,
        ?string $stockId = null
    ) {
        $this->poNumber = $poNumber;
        $this->supplierId = $supplierId;
        $this->orderDate = $orderDate;
        $this->stockId = $stockId;
    }

    public static function fromArray(array $data): self
    {
        $dto = new self(
            $data['po_number'],
            (int) $data['supplier_id'],
            new \DateTimeImmutable($data['order_date']),
            $data['stock_id'] ?? null
        );
        $dto->id = isset($data['id']) ? (int) $data['id'] : null;
        $dto->expectedDate = isset($data['expected_date'])
            ? new \DateTimeImmutable($data['expected_date']) : null;
        $dto->receivedDate = isset($data['received_date'])
            ? new \DateTimeImmutable($data['received_date']) : null;
        $dto->leadTimeDays = isset($data['lead_time_days']) ? (int) $data['lead_time_days'] : null;
        $dto->expectedLeadTimeDays = isset($data['expected_lead_time_days']) ? (int) $data['expected_lead_time_days'] : null;
        $dto->leadTimeVariance = isset($data['lead_time_variance']) ? (int) $data['lead_time_variance'] : null;
        $dto->qualityRating = $data['quality_rating'] ?? null;
        $dto->notes = $data['notes'] ?? null;
        return $dto;
    }

    public function calculateLeadTime(): void
    {
        if ($this->receivedDate !== null) {
            $diff = $this->orderDate->diff($this->receivedDate);
            $this->leadTimeDays = $diff->days;
        }

        if ($this->expectedDate !== null && $this->receivedDate !== null) {
            $expectedDiff = $this->orderDate->diff($this->expectedDate);
            $expectedDays = $expectedDiff->days;
            $this->leadTimeVariance = $this->leadTimeDays - $expectedDays;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoNumber(): string
    {
        return $this->poNumber;
    }

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getStockId(): ?string
    {
        return $this->stockId;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function getLeadTimeVariance(): ?int
    {
        return $this->leadTimeVariance;
    }

    public function isOnTime(): bool
    {
        if ($this->leadTimeVariance === null) {
            return true;
        }
        return $this->leadTimeVariance <= 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->poNumber,
            'supplier_id' => $this->supplierId,
            'stock_id' => $this->stockId,
            'order_date' => $this->orderDate->format('Y-m-d'),
            'expected_date' => $this->expectedDate !== null ? $this->expectedDate->format('Y-m-d') : null,
            'received_date' => $this->receivedDate !== null ? $this->receivedDate->format('Y-m-d') : null,
            'lead_time_days' => $this->leadTimeDays,
            'expected_lead_time_days' => $this->expectedLeadTimeDays,
            'lead_time_variance' => $this->leadTimeVariance,
            'quality_rating' => $this->qualityRating,
            'notes' => $this->notes,
        ];
    }
}