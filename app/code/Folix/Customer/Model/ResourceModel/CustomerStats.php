<?php
/**
 * Folix Customer Module - Customer Stats Resource Model
 *
 * 用户统计数据Resource Model
 */

namespace Folix\Customer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Customer Stats Resource Model
 */
class CustomerStats extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('folix_customer_stats', 'customer_id');
    }

    /**
     * 根据customer_id获取统计数据
     *
     * @param int $customerId
     * @return array
     */
    public function getStatsByCustomerId(int $customerId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId);
        
        $result = $connection->fetchRow($select);
        return $result ?: [];
    }

    /**
     * 更新或插入统计数据
     *
     * @param int $customerId
     * @param array $data
     * @return void
     */
    public function saveStats(int $customerId, array $data): void
    {
        $connection = $this->getConnection();
        $connection->insertOnDuplicate(
            $this->getMainTable(),
            ['customer_id' => $customerId] + $data,
            array_keys($data)
        );
    }

    /**
     * 重新计算用户统计数据（Cron任务使用）
     *
     * @param int $customerId
     * @return array
     */
    public function recalculateStats(int $customerId): array
    {
        $connection = $this->getConnection();
        
        // 计算总订单数和总消费金额
        $orderTable = $this->getTable('sales_order');
        $select = $connection->select()
            ->from($orderTable, [
                'total_orders' => 'COUNT(entity_id)',
                'total_spent' => 'SUM(grand_total)',
                'last_order_at' => 'MAX(created_at)'
            ])
            ->where('customer_id = ?', $customerId)
            ->where('status IN (?)', ['complete', 'processing', 'pending']);
        
        $stats = $connection->fetchRow($select) ?: [];
        
        // 更新统计表
        $this->saveStats($customerId, $stats);
        
        return $stats;
    }
}
