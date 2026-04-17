<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Api\Message\ProductDetailMessageInterface;
use FolixCode\ProductSync\Service\ProductDetailImporter;
use Psr\Log\LoggerInterface;

/**
 * 产品详情消费者
 */
class ProductDetailConsumer
{
    private ProductDetailImporter $productDetailImporter;
    private LoggerInterface $logger;

    public function __construct(
        ProductDetailImporter $productDetailImporter,
        LoggerInterface $logger
    ) {
        $this->productDetailImporter = $productDetailImporter;
        $this->logger = $logger;
    }

    /**
     * 处理产品详情消息
     *
     * @param ProductDetailMessageInterface $message
     * @return void
     * @throws \Exception
     */
    public function process(ProductDetailMessageInterface $message): void
    {
        $productId = $message->getProductId();

        if (!$productId) {
            $this->logger->warning('Invalid product ID received', [
                'product_id' => $productId
            ]);
            throw new \InvalidArgumentException('Product ID is required');
        }

        $startTime = microtime(true);

        $this->logger->info('Processing product detail import', [
            'product_id' => $productId
        ]);

        try {
            $this->productDetailImporter->import($productId);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Product detail import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Failed to process product detail import', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }
}