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
     * @throws \Exception 如果处理失败，抛出异常让 Magento 框架处理重试
     */
    public function process(OperationInterface $operation): void
    {
        $startTime = microtime(true);
        $categoryData = [];
        
        try {
            // 从 Operation 中获取序列化的数据并反序列化
            $serializedData = $operation->getSerializedData();
            $categoryData = $this->serializer->unserialize($serializedData);

            // 验证必填字段：id
            if (empty($categoryData['id'])) {
                throw new \InvalidArgumentException('Category ID is required');
            }

            // 验证必填字段：name
            if (empty($categoryData['name'])) {
                throw new \InvalidArgumentException('Category name is required');
            }

            $categoryId = $categoryData['id'];

            $this->logger->info('Processing category import', [
                'category_id' => $categoryId,
                'name' => $categoryData['name']
            ]);

            // 执行导入
            $this->categoryImporter->import($categoryData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Category import completed successfully', [
                'category_id' => $categoryId,
                'duration_ms' => $duration
            ]);

        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            // ✅ 分类已存在，视为成功（不抛出异常）
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Category already exists, skipped', [
                'category_id' => $categoryId,
                'duration_ms' => $duration
            ]);
            
        } catch (\Exception $e) {
            // ❌ 其他所有错误：记录日志并抛出异常
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process category import', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);
            
            // 抛出异常，让 Magento 框架自动处理重试逻辑
            throw $e;
        }
    }
}
