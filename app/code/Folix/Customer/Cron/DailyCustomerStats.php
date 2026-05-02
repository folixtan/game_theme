<?php
/**
 * Folix Customer Module - Daily Customer Stats Cron
 *
 * 每日用户统计 Cron 任务
 * 
 * 执行策略：
 * - 每 10 分钟执行一次
 * - 只统计最近时间段内有新订单的客户
 * - 按天统计，防止重复和遗漏
 */

namespace Folix\Customer\Cron;

use Folix\Customer\Model\ResourceModel\CustomerDailyStats as CustomerDailyStatsResource;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Daily Customer Stats Cron
 */
class DailyCustomerStats
{
    /**
     * @var CustomerDailyStatsResource
     */
    private $dailyStatsResource;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * 统计时间窗口（分钟）
     */
    private const TIME_WINDOW_MINUTES = 10;

    /**
     * @param CustomerDailyStatsResource $dailyStatsResource
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerDailyStatsResource $dailyStatsResource,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->dailyStatsResource = $dailyStatsResource;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute cron job - 增量统计
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('Starting daily customer stats calculation');
        
        try {
            // 1. 获取最近 N 分钟内有新订单的客户 ID 列表
            $customerOrders = $this->getRecentOrderCustomersWithDates();
            
            if (empty($customerOrders)) {
                $this->logger->info('No recent orders found, skipping stats update');
                return;
            }
            
            $this->logger->info(
                'Found {count} customers with recent orders',
                ['count' => count($customerOrders)]
            );
            
            // 2. 按客户和日期分组统计
            $processed = 0;
            $errors = 0;
            
            foreach ($customerOrders as $item) {
                try {
                    $customerId = (int)$item['customer_id'];
                    $statDate = $item['stat_date']; // Y-m-d 格式
                    
                    // 重新计算该客户该天的统计数据（幂等操作）
                    $this->dailyStatsResource->recalculateDailyStats($customerId, $statDate);
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->error(
                        'Failed to recalculate daily stats for customer {customer_id} on {date}: {error}',
                        [
                            'customer_id' => $item['customer_id'] ?? 'unknown',
                            'date' => $item['stat_date'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }
            
            $this->logger->info(
                'Daily stats calculation completed. Processed: {processed}, Errors: {errors}',
                [
                    'processed' => $processed,
                    'errors' => $errors
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical(
                'Daily customer stats cron job failed: {error}',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }

    /**
     * 获取最近 N 分钟内有新订单的客户 ID 和对应日期列表
     *
     * @return array 格式: [['customer_id' => 1, 'stat_date' => '2024-01-01'], ...]
     */
    private function getRecentOrderCustomersWithDates(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        
        // 计算时间窗口
        $sinceTime = date('Y-m-d H:i:s', strtotime('-' . self::TIME_WINDOW_MINUTES . ' minutes'));
        
        // 查询最近 N 分钟内有订单的客户和日期（去重）
        $select = $connection->select()
            ->from($orderTable, [
                'customer_id',
                'stat_date' => new \Zend_Db_Expr('DATE(created_at)')
            ])
            ->where('customer_id IS NOT NULL')
            ->where('customer_id > 0')
            ->where('created_at >= ?', $sinceTime)
            ->where('status IN (?)', ['complete', 'processing', 'pending'])
            ->group(['customer_id', 'DATE(created_at)']);
        
        $results = $connection->fetchAll($select);
        
        return $results ?: [];
    }
}
