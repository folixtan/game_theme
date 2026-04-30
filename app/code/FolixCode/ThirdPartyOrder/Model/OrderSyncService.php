<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\ThirdPartyOrder\Helper\Data as ThirdPartyHelper;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrder\ThirdPartyOrder as ThirdPartyOrderResource;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderPushFactory;
/**
 * Order Sync Service - 核心同步逻辑
 * 
 * 负责:
 * 1. 调用第三方API创建订单
 * 2. 处理响应并保存到数据库
 * 3. 状态映射和转换
 */
class OrderSyncService
{

   public const API_URI = '/api/user-order/create';
    

    private ExternalApiClientInterface $apiClient;
    private ThirdPartyOrderResource $resource;
    private \FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderDbFactory $thirdPartyOrderFactory;
    private OrderRepositoryInterface $orderRepository;
    private ThirdPartyHelper $helper;
    private Json $json;
    private TimezoneInterface $timezone;
    private LoggerInterface $logger;

    private ThirdPartyOrderPushFactory $thirdPartyOrderPushFactory;

    private ThirdPartyOrderDbRepository $thirdPartyOrderDbRepository;

    public function __construct(
        ExternalApiClientInterface $apiClient,
        ThirdPartyOrderResource $resource,
        \FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderDbFactory $thirdPartyOrderFactory,
        OrderRepositoryInterface $orderRepository,
        ThirdPartyHelper $helper,
        Json $json,
        ThirdPartyOrderDbRepository $thirdPartyOrderDbRepository,
        TimezoneInterface $timezone,
        ThirdPartyOrderPushFactory $thirdPartyOrderPushFactory,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->resource = $resource;
        $this->thirdPartyOrderFactory = $thirdPartyOrderFactory;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
        $this->json = $json;
        $this->timezone = $timezone;
        $this->logger = $logger;
        $this->thirdPartyOrderPushFactory = $thirdPartyOrderPushFactory;
        $this->thirdPartyOrderDbRepository = $thirdPartyOrderDbRepository;
    }

    /**
     * 同步订单到第三方
     *
     * @param OrderInterface $order
     * @return bool
     * @throws \Exception
     */
    public function syncOrder(OrderInterface $order): bool
    {
        $magentoOrderId = (int)$order->getId();
        $startTime = microtime(true);

        try {
            // 1. 检查是否已同步
            if ($this->resource->loadByMagentoOrderId($magentoOrderId)) {
                $this->logger->info('Order already synced, skipping', [
                    'magento_order_id' => $magentoOrderId
                ]);
                return true;
            }

            // 2. API请求数据
            $requestData = $this->buildCreateOrderRequest($order);      

            return true;

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Failed to sync order', [
                'magento_order_id' => $magentoOrderId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);

            // 更新同步失败状态
            $this->saveFailedStatus($magentoOrderId, $e->getMessage());

            throw $e;
        }
    }

    /**
     * 构建创建订单请求数据
     *
     * @param OrderInterface $order
     * @return ThirdPartyOrderPushManager
     */
    private function buildCreateOrderRequest(OrderInterface $order): ThirdPartyOrderPushManager
    {
        $orderData = [
          
            'notify_url' => $this->helper->getNotifyUrl(),
            
        ];

      

        // 遍历订单项
        foreach ($order->getItems() as $item) {
            $product = str_replace(\FolixCode\ProductSync\Service\ProductImporter::SKU_PREFIX,'', $item->getSku());
            /**
             * @var ThirdPartyOrderPushManager
             */
              $pushToData = $this->thirdPartyOrderPushFactory->create($orderData);
             $pushToData->setUserOrderId((string)$item->getItemId());
             // 2. 设置时间戳
             $pushToData->setTimestamp($this->timezone->date()->getTimestamp());
            $pushToData->setProductId($product);
            $pushToData->setBuyNum((string)$item->getQtyOrdered());
            if($chargeTemplate = $item->getAdditionalData()) {
                   $chargeInfo = $this->json->unserialize($chargeTemplate);
                   foreach($chargeInfo as $key => $value) {
                    $pushToData->setData($key,$value);
                   }
                 
            }
             // 3. 调用第三方API创建订单
            $response = $this->apiClient->post(self::API_URI, $pushToData->getData());
              $this->logger->info('Calling third party create order API', [
                'magento_order_id' => $item->getItemId(),
                'order_type' =>  $response['order_type'] ?? 'unknown',
            ]);


            // 5. 保存响应数据
             $response['increment_id'] = $order->getIncrementId();
             $response['customer_id'] = $order->getCustomerId();
          
            // 4. 处理响应
            $this->handleCreateOrderResponse($item, $response);
 
        }

       
    }

    /**
     * 处理创建订单响应
     *
     * @param OrderInterface $order
     * @param array $response
     */
    private function handleCreateOrderResponse(\Magento\Sales\Api\Data\OrderItemInterface $orderItem, array $response): void
    {
        $magentoOrderId = (int)$orderItem->getOrderId();
 
        // 创建记录
        $thirdPartyOrder = $this->thirdPartyOrderFactory->create([
            'data' => [
                'magento_order_id' => $magentoOrderId,
                'entity_id' => $orderItem->getItemId(),
                'customer_id' =>  $response['customer_id'],
                'increment_id' => $response['increment_id'],
                'third_party_order_id' => $response['order_id'] ?? '',
                'order_type' => $response['order_type'] ?? '',
                'status_code' => $response['status_code'] ?? 0,
                'charge_account' => $chargeInfo['charge_account'] ?? null,
                'charge_region' => $chargeInfo['charge_region'] ?? null,
                'sync_status' => 'synced',
                'synced_at' => $this->timezone->date()->format('Y-m-d H:i:s')
            ]
        ]);

        // 如果是卡密订单,保存卡密信息
        if (!empty($response['cards']) && is_array($response['cards'])) {
             $thirdPartyOrder->setCardNo($response['cards'][0]['card_no']);
             $thirdPartyOrder->setCardPwd($response['cards'][0]['card_pwd']);
             $thirdPartyOrder->setCardDeadline($response['cards'][0]['card_deadline']);
         
        }

        $this->$this->thirdPartyOrderDbRepository->save($thirdPartyOrder);
    }

    /**
     * 保存失败状态
     *
     * @param int $magentoOrderId
     * @param string $errorMessage
     */
    private function saveFailedStatus(int $magentoOrderId, string $errorMessage): void
    {
        try {
            $existingRecord = $this->resource->loadByMagentoOrderId($magentoOrderId);
            
            if (!$existingRecord) {
                $thirdPartyOrder = $this->thirdPartyOrderFactory->create([
                    'data' => [
                        'magento_order_id' => $magentoOrderId,
                        'sync_status' => 'failed'
                    ]
                ]);
                $thirdPartyOrder->save();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to save failed status', [
                'magento_order_id' => $magentoOrderId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
