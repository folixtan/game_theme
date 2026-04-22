<?php
/**
 * Folix Customer Module - Update Customer Stats Observer
 *
 * 监听订单完成事件，更新用户统计数据
 */

namespace Folix\Customer\Observer;

use Folix\Customer\Api\CustomerStatsRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Update Customer Stats Observer
 */
class UpdateCustomerStats implements ObserverInterface
{
    /**
     * @var CustomerStatsRepositoryInterface
     */
    private $customerStatsRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerStatsRepositoryInterface $customerStatsRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerStatsRepositoryInterface $customerStatsRepository,
        LoggerInterface $logger
    ) {
        $this->customerStatsRepository = $customerStatsRepository;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $order = $observer->getEvent()->getOrder();
            
            if (!$order || !$order->getCustomerId()) {
                return;
            }

            // 只处理已完成的订单
            if ($order->getStatus() !== 'complete') {
                return;
            }

            $customerId = (int)$order->getCustomerId();
            
            // 重新计算并更新统计数据
            $stats = $this->customerStatsRepository->recalculate($customerId);
            
            $this->logger->debug(
                'Customer stats updated for customer {customer_id}',
                [
                    'customer_id' => $customerId,
                    'stats' => $stats
                ]
            );
        } catch (\Exception $e) {
            // 记录错误但不中断订单流程
            $this->logger->error(
                'Failed to update customer stats: {error}',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }
}
