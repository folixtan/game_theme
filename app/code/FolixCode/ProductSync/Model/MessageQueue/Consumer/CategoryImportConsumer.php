<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Api\Message\CategoryImportMessageInterface;
use FolixCode\ProductSync\Service\CategoryImporter;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 分类导入消费者
 */
class CategoryImportConsumer
{
    private CategoryImporter $categoryImporter;
    private LoggerInterface $logger;
    private LoggerInterface $consumerLogger;
    private int $maxRetries = 3;
    private int $retryDelay = 60;

    public function __construct(
        CategoryImporter $categoryImporter,
        LoggerInterface $logger
    ) {
        $this->categoryImporter = $categoryImporter;
        $this->logger = $logger;

        // 创建独立的分类导入消费者日志记录器
        $this->consumerLogger = new Logger('category_import_consumer');
        $logPath = BP . '/var/log/category_import_consumer.log';
        $this->consumerLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    /**
     * 处理分类导入消息
     *
     * @param CategoryImportMessageInterface $message
     * @return void
     * @throws \Exception
     */
    public function process(CategoryImportMessageInterface $message): void
    {
        $categoryData = $message->getData();

        if (empty($categoryData['id'])) {
            $this->consumerLogger->warning('Invalid category data received', [
                'message' => json_encode($categoryData)
            ]);
            $this->logger->warning('Invalid category data received', [
                'message' => json_encode($categoryData)
            ]);
            throw new \InvalidArgumentException('Category ID is required');
        }

        $categoryId = $categoryData['id'] ?? 'unknown';
        $startTime = microtime(true);

        $this->consumerLogger->info('Processing category import', [
            'category_id' => $categoryId
        ]);
        $this->logger->info('Processing category import', [
            'category_id' => $categoryId
        ]);

        try {
            $this->categoryImporter->import($categoryData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->consumerLogger->info('Category import completed', [
                'category_id' => $categoryId,
                'duration_ms' => $duration
            ]);
            $this->logger->info('Category import completed', [
                'category_id' => $categoryId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->consumerLogger->error('Failed to process category import', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            $this->logger->error('Failed to process category import', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }

}