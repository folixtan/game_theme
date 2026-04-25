<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrder\ThirdPartyOrder as ThirdPartyOrderResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Dashboard Stats Updater
 * 
 * 更新客户统计数据(与FolixCustomerStats模块联动)
 */
class DashboardStatsUpdater
{
    private ThirdPartyOrderResource $resource;
    private ResourceConnection $resourceConnection;
    private LoggerInterface $logger;

    public function __construct(
        ThirdPartyOrderResource $resource,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * 更新客户的统计数据
     *
     * @param int $customerId
     * @return void
     */
    public function updateCustomerStats(int $customerId): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            
            // 统计成功订单数
            $totalOrders = $this->countOrdersByStatus($customerId, null);
            
            // 统计活跃卡密数量(status_code=2的订单中的卡密总数)
            $activeKeys = $this->sumActiveCards($customerId);
            
            // 统计直充订单数
            $directOrders = $this->countOrdersByType($customerId, 'direct');
            
            // 统计卡密订单数
            $cardOrders = $this->countOrdersByType($customerId, 'card');

            // TODO: 更新到 folix_customer_stats 表
            // 需要根据实际的表结构调整
            $this->logger->info('Customer stats calculated', [
                'customer_id' => $customerId,
                'total_orders' => $totalOrders,
                'active_keys' => $activeKeys,
                'direct_orders' => $directOrders,
                'card_orders' => $cardOrders
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update customer stats', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 统计指定状态的订单数量
     *
     * @param int $customerId
     * @param int|null $statusCode 状态码,null表示全部
     * @return int
     */
    private function countOrdersByStatus(int $customerId, ?int $statusCode): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('folix_third_party_orders');

        $select = $connection->select()
            ->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
            ->where('customer_id = ?', $customerId);

        if ($statusCode !== null) {
            $select->where('status_code = ?', $statusCode);
        }

        return (int)$connection->fetchOne($select);
    }

    /**
     * 统计指定类型的订单数量
     *
     * @param int $customerId
     * @param string $orderType
     * @return int
     */
    private function countOrdersByType(int $customerId, string $orderType): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('folix_third_party_orders');

        $select = $connection->select()
            ->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
            ->where('customer_id = ?', $customerId)
            ->where('order_type = ?', $orderType)
            ->where('status_code = ?', 2);  // 只统计成功的订单

        return (int)$connection->fetchOne($select);
    }

    /**
     * 统计活跃卡密数量
     *
     * @param int $customerId
     * @return int
     */
    private function sumActiveCards(int $customerId): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('folix_third_party_orders');

        $select = $connection->select()
            ->from($tableName, new \Zend_Db_Expr('SUM(cards_count)'))
            ->where('customer_id = ?', $customerId)
            ->where('status_code = ?', 2)  // 只统计成功的订单
            ->where('cards_count > ?', 0);

        return (int)$connection->fetchOne($select) ?: 0;
    }
}
