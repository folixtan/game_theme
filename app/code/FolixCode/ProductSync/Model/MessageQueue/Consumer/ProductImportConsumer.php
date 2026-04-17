<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Api\Message\ProductImportMessageInterface;
use FolixCode\ProductSync\Service\ProductImporter;
use Psr\Log\LoggerInterface;

/**
 * 产品导入消费者
 */
class ProductImportConsumer
{
    private ProductImporter $productImporter;
    private LoggerInterface $logger;

    public function __construct(
        ProductImporter $productImporter,
        LoggerInterface $logger
    ) {
        $this->productImporter = $productImporter;
        $this->logger = $logger;
    }

    /**
     * 处理产品导入消息
     *
     * @param ProductImportMessageInterface $message
     * @return void
     * @throws \Exception
     */
    public function process(ProductImportMessageInterface $message): void
    {
        $productData = $message->getData();

        if (empty($productData['id'])) {
            $this->logger->warning('Invalid product data received', [
                'data' => $productData
            ]);
            throw new \InvalidArgumentException('Product ID is required');
        }

        $productId = $productData['id'] ?? 'unknown';
        $startTime = microtime(true);

        $this->logger->info('Processing product import', [
            'product_id' => $productId
        ]);

        try {
            $this->productImporter->import($productData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Product import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Failed to process product import', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }
}