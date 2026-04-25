<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model\MessageQueue\Consumer;

use FolixCode\ThirdPartyOrder\Model\OrderSyncService;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Order Sync Consumer - 异步处理订单同步
 */
class OrderSyncConsumer
{
    private OrderSyncService $orderSyncService;
    private OrderRepositoryInterface $orderRepository;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        OrderSyncService $orderSyncService,
        OrderRepositoryInterface $orderRepository,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->orderSyncService = $orderSyncService;
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * 处理订单同步消息
     *
     * @param OperationInterface $operation
     * @return void
     * @throws \Exception
     */
    public function process(OperationInterface $operation): void
    {
        $startTime = microtime(true);

        try {
            // 反序列化消息数据
            $serializedData = $operation->getSerializedData();
            $orderData = $this->serializer->unserialize($serializedData);

            $orderId = $orderData['order_id'] ?? 0;
            if (!$orderId) {
                $this->logger->warning('Invalid order data, missing order_id');
                return;
            }

            $this->logger->info('Processing order sync', [
                'order_id' => $orderId,
                'increment_id' => $orderData['increment_id'] ?? 'unknown'
            ]);

            // 加载订单
            $order = $this->orderRepository->get($orderId);

            // 执行同步
            $this->orderSyncService->syncOrder($order);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Order sync completed successfully', [
                'order_id' => $orderId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process order sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);

            // 抛出异常,让Magento框架自动重试
            throw $e;
        }
    }
}
