<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model\MessageQueue;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\MessageQueue\PublisherInterface as MqPublisher;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * MQ Publisher - 发布订单同步消息
 */
class Publisher
{
    public const TOPIC_ORDER_SYNC = 'folixcode.order.sync';

    private MqPublisher $mqPublisher;
    private OperationInterfaceFactory $operationFactory;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        MqPublisher $mqPublisher,
        OperationInterfaceFactory $operationFactory,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->mqPublisher = $mqPublisher;
        $this->operationFactory = $operationFactory;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * 发布订单同步消息
     *
     * @param array $orderData
     * @return void
     * @throws \Exception
     */
    public function publishOrderSync(array $orderData): void
    {
        try {
            // 创建Operation对象
            $operation = $this->operationFactory->create([
                'data' => [
                    'topic_name' => self::TOPIC_ORDER_SYNC,
                    'serialized_data' => $this->serializer->serialize($orderData),
                    'status' => OperationInterface::STATUS_TYPE_OPEN
                ]
            ]);

            // 发布消息
            $this->mqPublisher->publish(self::TOPIC_ORDER_SYNC, $operation);

            $this->logger->info('Order sync message published', [
                'order_id' => $orderData['order_id'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish order sync message', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);
            throw $e;
        }
    }
}
