<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\ThirdPartyOrder\Api\Data\ApiResponseTransformerInterface;
use FolixCode\ThirdPartyOrder\Helper\Data as ThirdPartyHelper;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbResource as ThirdPartyOrderResource;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderPushFactory;
use Magento\Catalog\Model\ProductRepository;

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

    private ProductRepository $productRepository;

    private ApiResponseTransformerInterface $transformer;

    private EventManager $eventManager;

    private OrderStateUpdater $orderStateUpdater;

    public function __construct(
        ExternalApiClientInterface $apiClient,
        ThirdPartyOrderResource $resource,
        \FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderDbFactory $thirdPartyOrderFactory,
        OrderRepositoryInterface $orderRepository,
        ThirdPartyHelper $helper,
        Json $json,
        TimezoneInterface $timezone,
        ThirdPartyOrderPushFactory $thirdPartyOrderPushFactory,
        ProductRepository $productRepository,
        ApiResponseTransformerInterface $transformer,
        EventManager $eventManager,
        OrderStateUpdater $orderStateUpdater,
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
        $this->productRepository = $productRepository;
        $this->transformer = $transformer;
        $this->eventManager = $eventManager;
        $this->orderStateUpdater = $orderStateUpdater;
    }

    /**
     * Sync order to third party API
     *
     * @param OrderInterface $order
     * @return bool
     * @throws \Exception
     */
    public function syncOrder(OrderInterface $order): bool
    {
        // Check if order sync is enabled
        if (!$this->helper->isOrderSyncEnabled()) {
            $this->logger->info('Order sync is disabled by configuration', [
                'order_id' => $order->getEntityId()
            ]);
            return false;
        }

        $magentoOrderId = (int)$order->getEntityId();
        $startTime = microtime(true);

        // 遍历订单项
        foreach ($order->getItems() as $item) {
           $itemId = $item->getItemId();
           $this->logger->info('Processing order item', [
               'item_id' => $itemId,
               'order_type' => $item->getProductType(),
               'is_virtual' => $item->getIsVirtual(),
               'magento_order_id' => $magentoOrderId
           ]);

           if($item->getProductType() === 'configurable') continue;
          try { 
              $product = str_replace(\FolixCode\ProductSync\Service\ProductImporter::SKU_PREFIX,'', $item->getSku());
            
              // 构建请求数据
              $requestData = [
                  'user_notify_url' => $this->helper->getNotifyUrl(),
                  'user_order_id' => (string)$item->getItemId(),
                  'timestamp' => $this->timezone->date()->getTimestamp(),
                  'product_id' => $product,
                  'buy_num' => (int)$item->getQtyOrdered()
              ];
              
              // 获取商品属性
              $productModel = $this->productRepository->get($item->getSku());
              $requestData['charge_type'] = $productModel->getData(\FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE);

              // 合并额外数据（从 additional_data）
              if($chargeTemplate = $item->getAdditionalData()) {
                  $chargeInfo = $this->json->unserialize($chargeTemplate);
                  foreach($chargeInfo as $key => $value) {
                      $requestData[$key] = $value;
                  }
              }
              
              $pushToData = $this->thirdPartyOrderPushFactory->create($requestData);
              
              // 调用第三方API创建订单
              $response = $this->apiClient->post(self::API_URI, $pushToData->getData());

              
              $this->logger->info('Calling third party create order API', [
                  'item_id' => $itemId,
                  'magento_order_id' => $magentoOrderId,
                  'order_type' =>  $response['order_type'] ?? 'unknown',
              ]);

              // 添加额外信息到响应
              $response['increment_id'] = $order->getIncrementId();
              $response['customer_id'] = $order->getCustomerId();
              $response['email'] = $order->getCustomerEmail();
              $response['customer_name'] = $order->getCustomerName() ?? sprintf('%s %s', $order->getCustomerFirstname(), $order->getCustomerLastname());
          
              // 处理响应并保存（增量更新）
              $this->handleCreateOrderResponse($item, $response);
              
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync order item', [
                'item_id' => $itemId,
                'magento_order_id' => $magentoOrderId,
                'error' => $e->getMessage()
            ]);
             // 更新同步失败状态
            $this->saveFailedStatus((int)$itemId, $e->getMessage());
        }
        }

        // 所有 Items 处理完成后，检查是否全部同步成功
        $this->orderStateUpdater->checkAndMarkOrderComplete((int)$magentoOrderId);

        return true;
    }

    /**
     * 处理创建订单响应（增量更新）
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @param array $response
     */
    private function handleCreateOrderResponse(\Magento\Sales\Api\Data\OrderItemInterface $orderItem, array $response): void
    {
        $entityId = $orderItem->getItemId();
        
        try {
            // 1. 使用 Transformer 转换数据（返回完整的数据库字段）
            $transformedData = $this->transformer->transformCreateOrderResponse($response);
            
            // 2. 触发"同步前"事件（供应商可以监听并扩展）
            $this->eventManager->dispatch('thirdparty_order_before_sync', [
                'order_item' => $orderItem,
                'trans_item' => $transformedData,
                'response' => $response,
            ]);
            
            // 3. 构建更新数据（直接使用 Transformer 返回的数据 + 系统字段）
            $updateData = $transformedData;
            $updateData['sync_status'] = 'synced';
            $updateData['synced_at'] = $this->timezone->date()->format('Y-m-d H:i:s');
            
            // 4. 执行 UPDATE（基于 entity_id）
            $this->resource->updateByEntityId((int)$entityId, $updateData);
            
            /**
             *  
             */
            $transformedData['customer_email'] = $response['email'];
            $transformedData['customer_name'] = $response['customer_name'];
            $transformedData['product_name']  = $orderItem->getName();
            $transformedData['product_sku'] = $orderItem->getSku();
            $transformedData['entity_id'] = $entityId;
            $transformedData['order_id'] = $response['order_id'];
            $transformedData['increment_id'] = $response['increment_id'];
            // 5. 触发"同步后"事件
            
            $this->eventManager->dispatch('thirdparty_order_after_sync_success', [
                'item' => $transformedData,
                'entity_id' => $entityId
            ]);
            
            $this->logger->info('Order item synced successfully', [
                'entity_id' => $entityId,
                'third_party_order_id' => $response['order_id'] ?? ''
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle create order response', [
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 保存失败状态
     *
     * @param int $entityId
     * @param string $errorMessage
     */
    private function saveFailedStatus(int $entityId, string $errorMessage): void
    {
        try {
            $updateData = [
                'sync_status' => 'failed',
                'last_error' => substr($errorMessage, 0, 1000)
            ];
            
            $this->resource->updateByEntityId((int)$entityId, $updateData);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to save failed status', [
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
