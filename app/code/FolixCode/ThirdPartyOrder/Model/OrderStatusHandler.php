<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrder\ThirdPartyOrder as ThirdPartyOrderResource;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Order Status Handler - 处理订单状态更新
 */
class OrderStatusHandler
{
    /** 状态码常量 */
    public const STATUS_PROCESSING = 0;  // 处理中
    public const STATUS_SUCCESS = 2;     // 成功
    public const STATUS_FAILED = 3;      // 失败

    private ThirdPartyOrderResource $resource;
    private DashboardStatsUpdater $statsUpdater;
    private LoggerInterface $logger;

    public function __construct(
        ThirdPartyOrderResource $resource,
        DashboardStatsUpdater $statsUpdater,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->statsUpdater = $statsUpdater;
        $this->logger = $logger;
    }

    /**
     * 处理异步通知
     *
     * @param array $orderData
     * @return void
     * @throws \Exception
     */
    public function handleNotification(array $orderData): void
    {
        $thirdPartyOrderId = $orderData['order_id'] ?? '';
        if (empty($thirdPartyOrderId)) {
            $this->logger->warning('Missing third_party_order_id in notification');
            throw new \InvalidArgumentException('Missing order_id in notification data');
        }

        // 查找本地记录
        $record = $this->resource->loadByThirdPartyOrderId($thirdPartyOrderId);
        
        if (!$record) {
            $this->logger->warning('Third party order not found', [
                'third_party_order_id' => $thirdPartyOrderId
            ]);
            throw new \RuntimeException('Order not found: ' . $thirdPartyOrderId);
        }

        $magentoOrderId = (int)$record['magento_order_id'];
        $customerId = $record['customer_id'] ? (int)$record['customer_id'] : null;
        $statusCode = (int)($orderData['status_code'] ?? 0);
        $entityId = $record['entity_id'];

        $this->logger->info('Processing order status update', [
            'magento_order_id' => $magentoOrderId,
            'entity_id'      => $record['entity_id'],
            'third_party_order_id' => $thirdPartyOrderId,
            'status_code' => $statusCode
        ]);

        // 根据状态码处理
        switch ($statusCode) {
            case self::STATUS_SUCCESS:
                $this->handleSuccess($entityId, $customerId, $orderData);
                break;
                
            case self::STATUS_FAILED:
                $this->handleFailed($entityId, $orderData);
                break;
                
            case self::STATUS_PROCESSING:
            default:
                $this->handleProcessing($entityId, $orderData);
                break;
        }
    }

    /**
     * 处理成功状态
     *
     * @param int $magentoOrderId
     * @param int|null $customerId
     * @param array $orderData
     */
    private function handleSuccess(int $magentoOrderId, ?int $customerId, array $orderData): void
    {
        try {
            $updateData = [
                'status_code' => self::STATUS_SUCCESS,
                'sync_status' => 'synced'
            ];

            // 提取充值账号信息(直充)
            if (!empty($orderData['account']['charge_account'])) {
                $updateData['charge_account'] = $orderData['account']['charge_account'];
                $updateData['charge_region'] = $orderData['account']['charge_region'] ?? '';
            }

            // 提取卡密信息(卡密)
            if (!empty($orderData['cards']) && is_array($orderData['cards'])) {
                  foreach($orderData['cards'] as $card) {
                      $updateData['card_no'] = $card['card_no'];
                      $updateData['card_pwd'] = $card['card_pwd'];
                      $updateData['card_deadline'] = $card['card_deadline'];
                  } 
            }

            // 更新数据库
            $this->resource->updateOrderStatus($magentoOrderId, self::STATUS_SUCCESS, $updateData);

            $this->logger->info('Order marked as success', [
                'magento_order_id' => $magentoOrderId
            ]);

            // 更新客户统计数据
            if ($customerId) {
                $this->statsUpdater->updateCustomerStats($customerId);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle success status', [
                'magento_order_id' => $magentoOrderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理失败状态
     *
     * @param int $magentoOrderId
     * @param array $orderData
     */
    private function handleFailed(int $magentoOrderId, array $orderData): void
    {
        try {
            $updateData = [
                'status_code' => self::STATUS_FAILED,
                'sync_status' => 'failed'
            ];

            // 保存错误描述
            if (!empty($orderData['description'])) {
                $updateData['last_error'] = substr($orderData['description'], 0, 1000);
            }

            $this->resource->updateOrderStatus($magentoOrderId, self::STATUS_FAILED, $updateData);

            $this->logger->warning('Order marked as failed', [
                'magento_order_id' => $magentoOrderId,
                'description' => $orderData['description'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle failed status', [
                'magento_order_id' => $magentoOrderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理处理中状态
     *
     * @param int $magentoOrderId
     * @param array $orderData
     */
    private function handleProcessing(int $magentoOrderId, array $orderData): void
    {
        $this->logger->info('Order still processing', [
            'magento_order_id' => $magentoOrderId
        ]);
        
        // 无需特殊处理,继续等待
    }
}
