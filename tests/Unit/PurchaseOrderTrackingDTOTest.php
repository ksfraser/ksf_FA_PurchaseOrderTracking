<?php
declare(strict_types=1);

namespace Ksfraser\Tests\FrontAccounting\PurchaseOrderTracking;

use Ksfraser\FrontAccounting\PurchaseOrderTracking\LeadTimeDTO;
use Ksfraser\FrontAccounting\PurchaseOrderTracking\FillRateDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PO Tracking DTOs.
 *
 * @BABOK Related: BR-PO-001
 * @since 1.0.0
 */
class PurchaseOrderTrackingDTOTest extends TestCase
{
    public function testLeadTimeDTOCalculation(): void
    {
        $dto = new LeadTimeDTO(
            'PO-001',
            1,
            new \DateTimeImmutable('2026-09-01')
        );

        $dto->setExpectedDate(new \DateTimeImmutable('2026-09-08'));
        $dto->setReceivedDate(new \DateTimeImmutable('2026-09-10'));
        $dto->setExpectedLeadTimeDays(7);
        $dto->calculateLeadTime();

        $this->assertEquals(9, $dto->getLeadTimeDays());
        $this->assertEquals(2, $dto->getLeadTimeVariance());
        $this->assertFalse($dto->isOnTime());
    }

    public function testLeadTimeDTOOnTime(): void
    {
        $dto = new LeadTimeDTO(
            'PO-002',
            1,
            new \DateTimeImmutable('2026-09-01')
        );

        $dto->setExpectedDate(new \DateTimeImmutable('2026-09-08'));
        $dto->setReceivedDate(new \DateTimeImmutable('2026-09-07'));
        $dto->setExpectedLeadTimeDays(7);
        $dto->calculateLeadTime();

        $this->assertEquals(-1, $dto->getLeadTimeVariance());
        $this->assertTrue($dto->isOnTime());
    }

    public function testFillRateDTOFullyFulfilled(): void
    {
        $dto = new FillRateDTO('PO-001', 1, 1, 'TEST-001', 100.0);

        $dto->addReceivedQty(100.0, new \DateTimeImmutable('2026-09-10'));
        $dto->recalculateFillRate();

        $this->assertEquals(100.0, $dto->getFillRate());
        $this->assertTrue($dto->isFullyFulfilled());
        $this->assertEquals(0, $dto->getShortfall());
    }

    public function testFillRateDTOPartial(): void
    {
        $dto = new FillRateDTO('PO-001', 1, 1, 'TEST-001', 100.0);

        $dto->addReceivedQty(75.0, new \DateTimeImmutable('2026-09-10'));
        $dto->recalculateFillRate();

        $this->assertEquals(75.0, $dto->getFillRate());
        $this->assertFalse($dto->isFullyFulfilled());
        $this->assertEquals(25.0, $dto->getShortfall());
    }

    public function testFillRateDTOOverShipped(): void
    {
        $dto = new FillRateDTO('PO-001', 1, 1, 'TEST-001', 100.0);

        $dto->addReceivedQty(110.0, new \DateTimeImmutable('2026-09-10'));
        $dto->recalculateFillRate();

        $this->assertEqualsWithDelta(110.0, $dto->getFillRate(), 0.001);
    }
}