<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Api\Message\CategoryImportMessageInterface;
use FolixCode\ProductSync\Service\CategoryImporter;
use Psr\Log\LoggerInterface;

/**
 * 分类导入消费者
 */
class CategoryImportConsumer
{
    private CategoryImporter $categoryImporter;
    private LoggerInterface $logger;

    public function __construct(
        CategoryImporter $categoryImporter,
        LoggerInterface $logger
    ) {
        $this->categoryImporter = $categoryImporter;
        $this->logger = $logger;
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
            $this->logger->warning('Invalid category data received', [
                'data' => $categoryData
            ]);
            throw new \InvalidArgumentException('Category ID is required');
        }

        $categoryId = $categoryData['id'] ?? 'unknown';
        $startTime = microtime(true);

        $this->logger->info('Processing category import', [
            'category_id' => $categoryId
        ]);

        try {
            $this->categoryImporter->import($categoryData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Category import completed', [
                'category_id' => $categoryId,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Failed to process category import', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }

}