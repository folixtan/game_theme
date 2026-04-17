<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Service\CategoryImporter;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * 分类导入消费者
 */
class CategoryImportConsumer
{
    private CategoryImporter $categoryImporter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        CategoryImporter $categoryImporter,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->categoryImporter = $categoryImporter;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * 处理分类导入消息
     *
     * @param OperationInterface $operation
     * @return void
     */
    public function process(OperationInterface $operation): void
    {
        try {
            // 从 Operation 中获取序列化的数据并反序列化
            $serializedData = $operation->getSerializedData();
            $categoryData = $this->serializer->unserialize($serializedData);

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

            $this->categoryImporter->import($categoryData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Category import completed successfully', [
                'category_id' => $categoryId,
                'duration_ms' => $duration
            ]);

        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            // ✅ 分类已存在，视为成功
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Category already exists, skipped', [
                'category_id' => $categoryId ?? 'unknown',
                'duration_ms' => $duration
            ]);
            
        } catch (\Magento\Framework\DB\Adapter\LockWaitException | 
                 \Magento\Framework\DB\Adapter\DeadlockException |
                 \Magento\Framework\DB\Adapter\ConnectionException $e) {
            // ✅ 数据库锁等待/死锁，可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->warning('Database lock detected, will retry', [
                'category_id' => $categoryId ?? 'unknown',
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
            
        } catch (\Exception $e) {
            // ❌ 其他错误，不重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Failed to process category import', [
                'category_id' => $categoryId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }
}