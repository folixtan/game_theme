<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Api\Message\ProductDetailMessageInterface;
use FolixCode\ProductSync\Service\ProductDetailImporter;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 产品详情消费者
 */
class ProductDetailConsumer
{
    private ProductDetailImporter $productDetailImporter;
    private LoggerInterface $logger;
    private LoggerInterface $consumerLogger;
    private int $maxRetries = 3;
    private int $retryDelay = 60;

    public function __construct(
        ProductDetailImporter $productDetailImporter,
        LoggerInterface $logger
    ) {
        $this->productDetailImporter = $productDetailImporter;
        $this->logger = $logger;

        // 创建独立的产品详情消费者日志记录器
        $this->consumerLogger = new Logger('product_detail_consumer');
        $logPath = BP . '/var/log/product_detail_consumer.log';
        $this->consumerLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
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
            $this->consumerLogger->warning('Invalid product ID received', [
                'message' => json_encode($message)
            ]);
            $this->logger->warning('Invalid product ID received', [
                'message' => json_encode($message)
            ]);
            throw new \InvalidArgumentException('Product ID is required');
        }

        $startTime = microtime(true);

        $this->consumerLogger->info('Processing product detail import', [
            'product_id' => $productId
        ]);
        $this->logger->info('Processing product detail import', [
            'product_id' => $productId
        ]);

        try {
            $this->productDetailImporter->import($productId);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->consumerLogger->info('Product detail import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);
            $this->logger->info('Product detail import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->consumerLogger->error('Failed to process product detail import', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            $this->logger->error('Failed to process product detail import', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }
}