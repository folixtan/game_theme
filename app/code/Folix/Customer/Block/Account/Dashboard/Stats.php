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
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var array|null
     */
    private $cachedStats = null;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CurrentCustomer $currentCustomer
     * @param CustomerStatsRepositoryInterface $customerStatsRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        CustomerStatsRepositoryInterface $customerStatsRepository,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->customerStatsRepository = $customerStatsRepository;
        parent::__construct($context, $data);
    }

    /**
     * 获取统计数据（带缓存）
     *
     * @return array
     */
    private function getStats(): array
    {
        if ($this->cachedStats === null) {
            try {
                $customerId = $this->currentCustomer->getCustomerId();
                $stats = $this->customerStatsRepository->getByCustomerId($customerId);
                $this->cachedStats = [
                    'total_orders' => (int)$stats->getTotalOrders(),
                    'total_spent' => (float)$stats->getTotalSpent(),
                    'active_keys' => (int)$stats->getActiveKeysCount()
                ];
            } catch (NoSuchEntityException $e) {
                // 如果统计表没有数据，返回默认值
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
