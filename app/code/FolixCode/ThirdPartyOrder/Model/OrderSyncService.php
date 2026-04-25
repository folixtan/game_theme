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
    /** 订单类型常量 */
    public const ORDER_TYPE_DIRECT = 'direct';  // 直充
    public const ORDER_TYPE_CARD = 'card';      // 卡密

    private ExternalApiClientInterface $apiClient;
    private ThirdPartyOrderResource $resource;
    private \FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderFactory $thirdPartyOrderFactory;
    private OrderRepositoryInterface $orderRepository;
    private ChargeInfoExtractor $chargeInfoExtractor;
    private ThirdPartyHelper $helper;
    private Json $json;
    private TimezoneInterface $timezone;
    private LoggerInterface $logger;

    public function __construct(
        ExternalApiClientInterface $apiClient,
        ThirdPartyOrderResource $resource,
        \FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderFactory $thirdPartyOrderFactory,
        OrderRepositoryInterface $orderRepository,
        ChargeInfoExtractor $chargeInfoExtractor,
        ThirdPartyHelper $helper,
        Json $json,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->resource = $resource;
        $this->thirdPartyOrderFactory = $thirdPartyOrderFactory;
        $this->orderRepository = $orderRepository;
        $this->chargeInfoExtractor = $chargeInfoExtractor;
        $this->helper = $helper;
        $this->json = $json;
        $this->timezone = $timezone;
        $this->logger = $logger;
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

            // 2. 构建API请求数据
            $requestData = $this->buildCreateOrderRequest($order);

            $this->logger->info('Calling third party create order API', [
                'magento_order_id' => $magentoOrderId,
                'order_type' => $requestData['items'][0]['order_type'] ?? 'unknown'
            ]);

            // 3. 调用第三方API创建订单
            $response = $this->apiClient->post('/api/user-order-create/create', $requestData);

            // 4. 处理响应
            $this->handleCreateOrderResponse($order, $response);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Order synced successfully', [
                'magento_order_id' => $magentoOrderId,
                'third_party_order_id' => $response['order_id'] ?? 'unknown',
                'duration_ms' => $duration
            ]);

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
     * @return array
     */
    private function buildCreateOrderRequest(OrderInterface $order): array
    {
        $orderData = [
            'user_order_id' => $order->getIncrementId(),
            'notify_url' => $this->helper->getNotifyUrl(),
            'items' => []
        ];

        // 遍历订单项
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            
            // TODO: 根据产品属性判断订单类型(直充/卡密)
            // $orderType = $product->getData('game_charge_type');
            $orderType = self::ORDER_TYPE_CARD; // 临时默认

            $itemData = [
                'product_id' => $product->getSku(),
                'product_name' => $item->getName(),
                'quantity' => (int)$item->getQtyOrdered(),
                'price' => (float)$item->getPrice(),
                'order_type' => $orderType
            ];

            // 如果是直充产品,添加充值账号信息
            if ($orderType === self::ORDER_TYPE_DIRECT) {
                $chargeInfo = $this->chargeInfoExtractor->extractFromOrderItem($item);
                if ($chargeInfo) {
                    $itemData['charge_account'] = $chargeInfo['charge_account'];
                    $itemData['charge_region'] = $chargeInfo['charge_region'];
                    
                    $this->logger->info('Added charge info to order item', [
                        'order_item_id' => $item->getId(),
                        'charge_account' => $chargeInfo['charge_account'],
                        'charge_region' => $chargeInfo['charge_region']
                    ]);
                } else {
                    $this->logger->warning('Missing charge info for direct order', [
                        'order_item_id' => $item->getId()
                    ]);
                }
            }

            $orderData['items'][] = $itemData;
        }

        return $orderData;
    }

    /**
     * 处理创建订单响应
     *
     * @param OrderInterface $order
     * @param array $response
     */
    private function handleCreateOrderResponse(OrderInterface $order, array $response): void
    {
        $magentoOrderId = (int)$order->getId();

        // 提取充值信息(用于保存)
        $chargeInfo = $this->chargeInfoExtractor->extractFromOrder($order);

        // 创建记录
        $thirdPartyOrder = $this->thirdPartyOrderFactory->create([
            'data' => [
                'magento_order_id' => $magentoOrderId,
                'customer_id' => $order->getCustomerId(),
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
            $thirdPartyOrder->setCardKeys(json_encode($response['cards']));
            $thirdPartyOrder->setCardsCount(count($response['cards']));
        }

        $thirdPartyOrder->save();
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
