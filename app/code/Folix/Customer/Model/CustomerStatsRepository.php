<?php
/**
 * Folix Customer Module - Customer Stats Repository
 *
 * 用户统计数据Repository实现
 */

namespace Folix\Customer\Model;

use Folix\Customer\Api\CustomerStatsRepositoryInterface;
use Folix\Customer\Model\ResourceModel\CustomerDailyStats as CustomerDailyStatsResource;

/**
 * Customer Stats Repository
 */
class CustomerStatsRepository implements CustomerStatsRepositoryInterface
{
    /**
     * @var CustomerDailyStatsResource
     */
    private $dailyStatsResource;

    /**
     * @param CustomerDailyStatsResource $dailyStatsResource
     */
    public function __construct(
        CustomerDailyStatsResource $dailyStatsResource
    ) {
        $this->dailyStatsResource = $dailyStatsResource;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerTotalStats(int $customerId): array
    {
        // 聚合所有天的数据
        return $this->dailyStatsResource->getAggregatedStats($customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerStatsByRange(int $customerId, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->dailyStatsResource->getAggregatedStats($customerId, $startDate, $endDate);
    }

    /**
     * @inheritDoc
     */
    public function recalculateDailyStats(int $customerId, string $statDate): array
    {
        return $this->dailyStatsResource->recalculateDailyStats($customerId, $statDate);
    }
}
