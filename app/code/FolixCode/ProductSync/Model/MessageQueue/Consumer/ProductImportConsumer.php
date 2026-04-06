<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Api\Message\ProductImportMessageInterface;
use FolixCode\ProductSync\Service\ProductImporter;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 产品导入消费者
 */
class ProductImportConsumer
{
    private ProductImporter $productImporter;
    private LoggerInterface $logger;
    private LoggerInterface $consumerLogger;
    private Json $json;
    private int $maxRetries = 3;
    private int $retryDelay = 60;

    public function __construct(
        ProductImporter $productImporter,
        LoggerInterface $logger,
        Json $json
    ) {
        $this->productImporter = $productImporter;
        $this->logger = $logger;
        $this->json = $json;

        // 创建独立的产品导入消费者日志记录器
        $this->consumerLogger = new Logger('product_import_consumer');
        $logPath = BP . '/var/log/product_import_consumer.log';
        $this->consumerLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
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
            $this->consumerLogger->warning('Invalid product data received', [
                'message' => json_encode($productData)
            ]);
            $this->logger->warning('Invalid product data received', [
                'message' => json_encode($productData)
            ]);
            throw new \InvalidArgumentException('Product ID is required');
        }

        $productId = $productData['id'] ?? 'unknown';
        $startTime = microtime(true);

        $this->consumerLogger->info('Processing product import', [
            'product_id' => $productId
        ]);
        $this->logger->info('Processing product import', [
            'product_id' => $productId
        ]);

        try {
            $this->productImporter->import($productData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->consumerLogger->info('Product import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);
            $this->logger->info('Product import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->consumerLogger->error('Failed to process product import', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            $this->logger->error('Failed to process product import', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }
}