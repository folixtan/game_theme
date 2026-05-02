<?php
/**
 * Folix Customer Module - Customer Daily Stats Resource Model
 *
 * 用户每日统计数据 Resource Model
 * 
 * 设计原则：
 * - 按天统计：每天每个客户一条记录
 * - 防重复：通过唯一索引 (customer_id + stat_date) 保证
 * - 增量更新：使用 INSERT ON DUPLICATE KEY UPDATE
 */

namespace Folix\Customer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Customer Daily Stats Resource Model
 */
class CustomerDailyStats extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('folix_customer_daily_stats', 'entity_id');
    }

    /**
     * 保存或更新每日统计数据（防重复）
     *
     * @param int $customerId
     * @param string $statDate 日期格式: Y-m-d
     * @param array $data
     * @return void
     */
    public function saveDailyStats(int $customerId, string $statDate, array $data): void
    {
        $connection = $this->getConnection();
        
        // 使用 INSERT ON DUPLICATE KEY UPDATE 防止重复
        $connection->insertOnDuplicate(
            $this->getMainTable(),
            [
                'customer_id' => $customerId,
                'stat_date' => $statDate,
            ] + $data,
            array_keys($data)  // 冲突时更新的字段
        );
    }

    /**
     * 获取客户某天的统计数据
     *
     * @param int $customerId
     * @param string $statDate
     * @return array
     */
    public function getStatsByDate(int $customerId, string $statDate): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('stat_date = ?', $statDate);
        
        $result = $connection->fetchRow($select);
        return $result ?: [];
    }

    /**
     * 获取客户指定日期范围内的统计数据总和
     *
     * @param int $customerId
     * @param string|null $startDate 开始日期 (Y-m-d)，null 表示不限制
     * @param string|null $endDate 结束日期 (Y-m-d)，null 表示不限制
     * @return array
     */
    public function getAggregatedStats(int $customerId, ?string $startDate = null, ?string $endDate = null): array
    {
        $connection = $this->getConnection();
        
        $select = $connection->select()
            ->from($this->getMainTable(), [
                'total_orders' => 'SUM(orders_count)',
                'total_spent' => 'SUM(orders_amount)',
                'active_keys' => 'SUM(active_keys_count)'
            ])
            ->where('customer_id = ?', $customerId);
        
        if ($startDate) {
            $select->where('stat_date >= ?', $startDate);
        }
        
        if ($endDate) {
            $select->where('stat_date <= ?', $endDate);
        }
        
        $result = $connection->fetchRow($select);
        return $result ?: [
            'total_orders' => 0,
            'total_spent' => 0.0,
            'active_keys' => 0
        ];
    }

    /**
     * 重新计算客户某天的统计数据（幂等操作）
     *
     * @param int $customerId
     * @param string $statDate
     * @return array
     */
    public function recalculateDailyStats(int $customerId, string $statDate): array
    {
        $connection = $this->getConnection();
        
        // 1. 计算当天的订单数和金额
        $orderTable = $this->getTable('sales_order');
        $dateStart = $statDate . ' 00:00:00';
        $dateEnd = $statDate . ' 23:59:59';
        
        $orderSelect = $connection->select()
            ->from($orderTable, [
                'orders_count' => 'COUNT(entity_id)',
                'orders_amount' => 'SUM(grand_total)'
            ])
            ->where('customer_id = ?', $customerId)
            ->where('created_at >= ?', $dateStart)
            ->where('created_at <= ?', $dateEnd)
            ->where('status IN (?)', ['complete', 'processing', 'pending']);
        
        $orderStats = $connection->fetchRow($orderSelect) ?: [
            'orders_count' => 0,
            'orders_amount' => 0.0
        ];
        
        // 2. 计算当天的活跃卡密数量（goods_type = 4）
        $thirdPartyOrderTable = $this->getTable('folix_third_party_orders');
        $keysSelect = $connection->select()
            ->from($thirdPartyOrderTable, ['active_keys_count' => 'COUNT(*)'])
            ->where('customer_id = ?', $customerId)
            ->where('goods_type = ?', 4)  // goods_type = 4 表示卡密
            ->where('sync_status = ?', 'synced')
            ->where('created_at >= ?', $dateStart)
            ->where('created_at <= ?', $dateEnd);
        
        $keysStats = $connection->fetchRow($keysSelect);
        $orderStats['active_keys_count'] = (int)($keysStats['active_keys_count'] ?? 0);
        
        // 3. 保存到统计表（防重复）
        $this->saveDailyStats($customerId, $statDate, $orderStats);
        
        return $orderStats;
    }
}
