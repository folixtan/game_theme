<?php
/**
 * Folix Customer Module - Customer Stats Repository Interface
 *
 * 用户统计数据Repository接口
 */

namespace Folix\Customer\Api;

/**
 * Customer Stats Repository Interface
 */
interface CustomerStatsRepositoryInterface
{
    /**
     * 获取用户累计统计数据（从开始到现在）
     *
     * @param int $customerId
     * @return array ['total_orders' => int, 'total_spent' => float, 'active_keys' => int]
     */
    public function getCustomerTotalStats(int $customerId): array;

    /**
     * 获取用户指定日期范围内的统计数据
     *
     * @param int $customerId
     * @param string|null $startDate 开始日期 (Y-m-d)
     * @param string|null $endDate 结束日期 (Y-m-d)
     * @return array ['total_orders' => int, 'total_spent' => float, 'active_keys' => int]
     */
    public function getCustomerStatsByRange(int $customerId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * 重新计算用户某天的统计数据（幂等操作）
     *
     * @param int $customerId
     * @param string $statDate 日期 (Y-m-d)
     * @return array
     */
    public function recalculateDailyStats(int $customerId, string $statDate): array;
}
