<?php
/**
 * Copyright © Folix Game Theme. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Folix\Customer\Block\Account\Dashboard;

use Folix\Customer\Model\ResourceModel\CustomerDailyStats as CustomerDailyStatsResource;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;

/**
 * Dashboard Stats Block
 * 
 * 负责渲染购买统计数据
 * - Total Orders: 总订单数
 * - Total Spent: 总消费金额
 * - Active Keys: 活跃卡密数
 * - Direct Charges: 直充数量
 */
class Stats extends Template
{
    /**
     * @var CustomerDailyStatsResource
     */
    private $customerDailyStatsResource;

    /**
     * @var CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array|null
     */
    private $cachedStats = null;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CurrentCustomer $currentCustomer
     * @param CustomerDailyStatsResource $customerDailyStatsResource
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        CustomerDailyStatsResource $customerDailyStatsResource,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->customerDailyStatsResource = $customerDailyStatsResource;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * 获取统计数据（从统计表查询）
     *
     * @return array
     */
    private function getStats(): array
    {
        if ($this->cachedStats === null) {
            try {
                $customerId = $this->currentCustomer->getCustomerId();
                
                // ✅ 从统计表获取聚合数据（极速查询）
                $stats = $this->customerDailyStatsResource->getAggregatedStats($customerId);
                
                $this->cachedStats = [
                    'total_orders' => (int)($stats['total_orders'] ?? 0),
                    'total_spent' => (float)($stats['total_spent'] ?? 0.0),
                    'active_keys' => (int)($stats['active_keys'] ?? 0),
                    'direct_charges' => (int)($stats['direct_charges'] ?? 0)
                ];
            } catch (\Exception $e) {
                // 如果查询失败，返回默认值
                $this->logger->error('Failed to get customer stats', [
                    'customer_id' => $customerId ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                
                $this->cachedStats = [
                    'total_orders' => 0,
                    'total_spent' => 0.0,
                    'active_keys' => 0,
                    'direct_charges' => 0
                ];
            }
        }
        
        return $this->cachedStats;
    }

    /**
     * 获取总订单数
     *
     * @return int
     */
    public function getTotalOrders(): int
    {
        $stats = $this->getStats();
        return $stats['total_orders'];
    }

    /**
     * 获取总消费金额
     *
     * @return float
     */
    public function getTotalSpent(): float
    {
        $stats = $this->getStats();
        return $stats['total_spent'];
    }

    /**
     * 获取活跃卡密数
     *
     * @return int
     */
    public function getActiveKeys(): int
    {
        $stats = $this->getStats();
        return $stats['active_keys'];
    }

    /**
     * 获取直充数量
     *
     * @return int
     */
    public function getDirectCharges(): int
    {
        $stats = $this->getStats();
        return $stats['direct_charges'];
    }
}
