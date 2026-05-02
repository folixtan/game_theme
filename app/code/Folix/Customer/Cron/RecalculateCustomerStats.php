<?php
/**
 * Folix Customer Module - Recalculate Customer Stats Cron
 *
 * 定时校准用户统计数据（防止Observer遗漏）
 */

namespace Folix\Customer\Cron;

use Folix\Customer\Api\CustomerStatsRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Recalculate Customer Stats Cron
 */
class RecalculateCustomerStats
{
    /**
     * @var CustomerStatsRepositoryInterface
     */
    private $customerStatsRepository;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerStatsRepositoryInterface $customerStatsRepository
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerStatsRepositoryInterface $customerStatsRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->customerStatsRepository = $customerStatsRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('Starting customer stats recalculation cron job');
        
        try {
            // 获取所有有订单的客户
            $customerCollection = $this->customerCollectionFactory->create();
            $customerCollection->getSelect()->join(
                ['order' => 'sales_order'],
                'main_table.entity_id = order.customer_id',
                []
            )->group('main_table.entity_id');
            
            $processed = 0;
            $errors = 0;
           echo $customerCollection->getSelect();exit;
            foreach ($customerCollection as $customer) {
                try {
                    $customerId = (int)$customer->getId();
                    var_dump($customerId);
                    $this->customerStatsRepository->recalculate($customerId);
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->error(
                        'Failed to recalculate stats for customer {customer_id}: {error}',
                        [
                            'customer_id' => $customerId ?? 'unknown',
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }
            
            $this->logger->info(
                'Customer stats recalculation completed. Processed: {processed}, Errors: {errors}',
                [
                    'processed' => $processed,
                    'errors' => $errors
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical(
                'Customer stats recalculation cron job failed: {error}',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }
}
