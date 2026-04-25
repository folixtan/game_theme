<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\BaseSyncService\Helper\Data as BaseSyncHelper;
use FolixCode\ThirdPartyOrder\Api\ThirdPartyOrderManagementInterface;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrder\ThirdPartyOrder as ThirdPartyOrderResource;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;


/**
 * Third Party Order Management Service Implementation
 */
class ThirdPartyOrderManagement implements ThirdPartyOrderManagementInterface
{
    private ThirdPartyOrderResource $resource;
    private OrderStatusHandler $orderStatusHandler;
    private BaseSyncHelper $baseHelper;
    private LoggerInterface $logger;

    public function __construct(
        ThirdPartyOrderResource $resource,
        OrderStatusHandler $orderStatusHandler,
        BaseSyncHelper $baseHelper,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->baseHelper = $baseHelper;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function handleNotification(array $notificationData): bool
    {
        try {
            $this->logger->info('Received third party notification via REST API', [
                'has_data' => isset($notificationData['data']),
                'has_secret_id' => isset($notificationData['secret_id'])
            ]);

            // 1. 验证签名(如果需要)
            if (!$this->validateSignature($notificationData)) {
                throw new LocalizedException(__('Invalid signature'));
            }

            // 2. 解密data字段
            if (!isset($notificationData['data'])) {
                throw new LocalizedException(__('Missing data field'));
            }

            $orderData = $this->baseHelper->decryptResponseData($notificationData['data']);
            
            if (empty($orderData) || !isset($orderData['order_id'])) {
                throw new LocalizedException(__('Invalid order data after decryption'));
            }

            $this->logger->info('Notification data decrypted successfully', [
                'third_party_order_id' => $orderData['order_id'],
                'status_code' => $orderData['status_code'] ?? 'unknown'
            ]);

            // 3. 处理订单状态更新
            $this->orderStatusHandler->handleNotification($orderData);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function getByMagentoOrderId(int $magentoOrderId): ?array
    {
        $record = $this->resource->loadByMagentoOrderId($magentoOrderId);
        
        if (!$record) {
            return null;
        }

        // 格式化返回数据
        return $this->formatOrderData($record);
    }

    /**
     * @inheritdoc
     */
    public function getByThirdPartyOrderId(string $thirdPartyOrderId): ?array
    {
        $record = $this->resource->loadByThirdPartyOrderId($thirdPartyOrderId);
        
        if (!$record) {
            return null;
        }

        return $this->formatOrderData($record);
    }

    /**
     * 验证签名
     *
     * @param array $params
     * @return bool
     */
    private function validateSignature(array $params): bool
    {
        // TODO: 根据第三方文档实现签名验证逻辑
        // 示例伪代码:
        // $expectedSign = $params['sign'];
        // $calculatedSign = $this->calculateSignature($params);
        // return hash_equals($expectedSign, $calculatedSign);
        
        // 暂时跳过,等待确认签名算法
        return true;
    }

    /**
     * 格式化订单数据返回
     *
     * @param array $record
     * @return array
     */
    private function formatOrderData(array $record): array
    {
        // 解析卡密JSON
        $cardKeys = [];
        if (!empty($record['card_keys'])) {
            $cardKeys = json_decode($record['card_keys'], true) ?: [];
        }

        return [
            'entity_id' => (int)$record['entity_id'],
            'magento_order_id' => (int)$record['magento_order_id'],
            'customer_id' => $record['customer_id'] ? (int)$record['customer_id'] : null,
            'third_party_order_id' => $record['third_party_order_id'],
            'order_type' => $record['order_type'],
            'status_code' => (int)$record['status_code'],
            'charge_account' => $record['charge_account'],
            'charge_region' => $record['charge_region'],
            'card_keys' => $cardKeys,
            'cards_count' => (int)$record['cards_count'],
            'sync_status' => $record['sync_status'],
            'synced_at' => $record['synced_at'],
            'created_at' => $record['created_at'],
            'updated_at' => $record['updated_at']
        ];
    }
}
