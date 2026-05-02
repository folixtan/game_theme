<?php
/**
 * Copyright © Folix Game Theme. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Folix\Customer\Block\Account\Dashboard;

use Folix\Customer\Api\CustomerStatsRepositoryInterface;
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
 */
class Stats extends Template
{
    /**
     * @var CustomerStatsRepositoryInterface
     */
    private $customerStatsRepository;

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
     * @param CustomerStatsRepositoryInterface $customerStatsRepository
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        CustomerStatsRepositoryInterface $customerStatsRepository,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->customerStatsRepository = $customerStatsRepository;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /*     * 获取统计数据（直接查询，不依赖统计表）
     *
     * @return array
     */
    private function getStats(): array
    {
        if ($this->cachedStats === null) {
            try {
                $customerId = $this->currentCustomer->getCustomerId();
                $resource = $this->_resource->getConnection();
                
                // 1. 查询订单统计
                $orderTable = $this->_resource->getTableName('sales_order');
                $orderSelect = $resource->select()
                    ->from($orderTable, [
                        'total_orders' => 'COUNT(entity_id)',
                        'total_spent' => 'SUM(grand_total)'
                    ])
                    ->where('customer_id = ?', $customerId)
                    ->where('status IN (?)', ['complete', 'processing', 'pending']);
                
                $orderStats = $resource->fetchRow($orderSelect) ?: [
                    'total_orders' => 0,
                    'total_spent' => 0.0
                ];
                
                // 2. 查询活跃卡密数量（goods_type = 4）
                $thirdPartyOrderTable = $this->_resource->getTableName('folix_third_party_orders');
                $keysSelect = $resource->select()
                    ->from($thirdPartyOrderTable, ['active_keys' => 'COUNT(*)'])
                    ->where('customer_id = ?', $customerId)
                    ->where('goods_type = ?', 4)
                    ->where('sync_status = ?', 'synced');
                
                $keysStats = $resource->fetchRow($keysSelect);
                
                $this->cachedStats = [
                    'total_orders' => (int)($orderStats['total_orders'] ?? 0),
                    'total_spent' => (float)($orderStats['total_spent'] ?? 0.0),
                    'active_keys' => (int)($keysStats['active_keys'] ?? 0)
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
                    'active_keys' => 0
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
}
