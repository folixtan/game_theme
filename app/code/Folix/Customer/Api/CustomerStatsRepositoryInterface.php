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
     * 获取用户统计数据
     *
     * @param int $customerId
     * @return array
     */
    public function getByCustomerId(int $customerId): array;

    /**
     * 更新用户统计数据
     *
     * @param int $customerId
     * @param array $data
     * @return void
     */
    public function save(int $customerId, array $data): void;

    /**
     * 重新计算用户统计数据
     *
     * @param int $customerId
     * @return array
     */
    public function recalculate(int $customerId): array;
}
