<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\ThirdPartyOrder\Api\Data\ApiResponseTransformerInterface;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbResource as ThirdPartyOrderResource;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
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
    private ApiResponseTransformerInterface $transformer;
    private EventManager $eventManager;
    private OrderStateUpdater $orderStateUpdater;
    private LoggerInterface $logger;

    public function __construct(
        ThirdPartyOrderResource $resource,
        DashboardStatsUpdater $statsUpdater,
        ApiResponseTransformerInterface $transformer,
        EventManager $eventManager,
        OrderStateUpdater $orderStateUpdater,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->statsUpdater = $statsUpdater;
        $this->transformer = $transformer;
        $this->eventManager = $eventManager;
        $this->orderStateUpdater = $orderStateUpdater;
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

        $entityId = (int)$record['entity_id'];
        $magentoOrderId = (int)$record['magento_order_id'];
        $customerId = $record['customer_id'] ? (int)$record['customer_id'] : null;
        $statusCode = (int)($orderData['status_code'] ?? 0);

        $this->logger->info('Processing order status update', [
            'entity_id' => $entityId,
            'magento_order_id' => $magentoOrderId,
            'third_party_order_id' => $thirdPartyOrderId,
            'status_code' => $statusCode
        ]);

        // 根据状态码处理
        switch ($statusCode) {
            case self::STATUS_SUCCESS:
                $this->handleSuccess($entityId, $magentoOrderId, $customerId, $orderData);
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
     * @param int $entityId
     * @param int $magentoOrderId
     * @param int|null $customerId
     * @param array $orderData
     */
    private function handleSuccess(int $entityId, int $magentoOrderId, ?int $customerId, array $orderData): void
    {
        try {
            // 1. 使用 Transformer 转换数据（返回完整的数据库字段）
            $transformedData = $this->transformer->transformNotificationResponse($orderData);
            
            // 2. 触发"同步前"事件
            $this->eventManager->dispatch('thirdparty_order_before_sync', [
                'entity_id' => $entityId,
                'data' => $transformedData
            ]);
            
            // 3. 构建更新数据（直接使用 Transformer 返回的数据 + 系统字段）
            $updateData = $transformedData;
            $updateData['status_code'] = self::STATUS_SUCCESS;
            $updateData['sync_status'] = 'synced';
            $updateData['synced_at'] = date('Y-m-d H:i:s');

            // 4. 执行 UPDATE
            $this->resource->updateByEntityId($entityId, $updateData);
            
            // 5. 触发"同步后"事件
            $this->eventManager->dispatch('thirdparty_order_after_sync_success', [
                'entity_id' => $entityId,
                'data' => $transformedData
            ]);

            $this->logger->info('Order item marked as success', [
                'entity_id' => $entityId
            ]);

            // 6. 更新客户统计数据
            if ($customerId) {
                $this->statsUpdater->updateCustomerStats($customerId);
            }
            
            // 7. 检查并更新整个订单的状态
            $this->orderStateUpdater->checkAndMarkOrderComplete($magentoOrderId);

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle success status', [
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理失败状态
     *
     * @param int $entityId
     * @param array $orderData
     */
    private function handleFailed(int $entityId, array $orderData): void
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

            $this->resource->updateByEntityId($entityId, $updateData);

            $this->logger->warning('Order item marked as failed', [
                'entity_id' => $entityId,
                'description' => $orderData['description'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle failed status', [
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理处理中状态
     *
     * @param int $entityId
     * @param array $orderData
     */
    private function handleProcessing(int $entityId, array $orderData): void
    {
        $this->logger->info('Order item still processing', [
            'entity_id' => $entityId
        ]);
        
        // 无需特殊处理,继续等待
    }
}
