<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Service\ProductDetailImporter;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * 产品详情消费者
 */
class ProductDetailConsumer
{
    private ProductDetailImporter $productDetailImporter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        ProductDetailImporter $productDetailImporter,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->productDetailImporter = $productDetailImporter;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * 处理产品详情消息
     *
     * @param OperationInterface $operation
     * @return void
     * @throws \Exception
     */
    public function process(OperationInterface $operation): void
    {
        try {
            // 从 Operation 中获取序列化的数据并反序列化
            $serializedData = $operation->getSerializedData();
            $data = $this->serializer->unserialize($serializedData);

            $productId = $data['product_id'] ?? null;

            if (!$productId) {
                $this->logger->warning('Invalid product ID received', [
                    'data' => $data
                ]);
                throw new \InvalidArgumentException('Product ID is required');
            }

            $startTime = microtime(true);

            $this->logger->info('Processing product detail import', [
                'product_id' => $productId
            ]);

            $this->productDetailImporter->import($productId);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Product detail import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Failed to process product detail import', [
                'product_id' => $productId ?? 'unknown',
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }
}