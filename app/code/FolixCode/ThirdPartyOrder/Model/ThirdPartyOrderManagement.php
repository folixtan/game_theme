<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;


use FolixCode\ThirdPartyOrder\Api\ThirdPartyOrderManagementInterface;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbResource as ThirdPartyOrderResource;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use FolixCode\BaseSyncService\Api\EncryptionStrategyInterface;
use Magento\Framework\Serialize\Serializer\Json;


/**
 * Third Party Order Management Service Implementation
 */
class ThirdPartyOrderManagement implements ThirdPartyOrderManagementInterface
{
    private ThirdPartyOrderResource $resource;
    private OrderStatusHandler $orderStatusHandler;
    private EncryptionStrategyInterface $encryption;
    private LoggerInterface $logger;
    private Json $json;

    public function __construct(
        ThirdPartyOrderResource $resource,
        OrderStatusHandler $orderStatusHandler,
        EncryptionStrategyInterface $encryption,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->encryption = $encryption;
        $this->logger = $logger;
        $this->json = $json;
    }

    /**
     * @inheritdoc
     */
    public function handleNotification(string $data): int
    {
        try {
             
           if(empty($data)) throw new  LocalizedException(__('Empty data'));

             

            $orderData = $this->encryption->decrypt($data);
            
            if (empty($orderData) || !isset($orderData['order_id'])) {
                throw new LocalizedException(__('Invalid order data after decryption'));
            }

            $this->logger->info('Notification data decrypted successfully', [
                'third_party_order_id' => $orderData['order_id'],
                'status_code' => $orderData['status_code'] ?? 'unknown'
            ]);

            // 3. 调用 OrderStatusHandler 处理订单状态更新
            $this->orderStatusHandler->handleNotification($orderData);

            $this->logger->info('Notification processed successfully, returning HTTP 200', [
                'third_party_order_id' => $orderData['order_id']
            ]);

            return 200;

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__($e->getMessage()));
        }
    }
 
}
