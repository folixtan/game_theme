<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Service\CategoryImporter;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
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
    private EntityManager $entityManager;

    public function __construct(
        CategoryImporter $categoryImporter,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        EntityManager $entityManager
    ) {
        $this->categoryImporter = $categoryImporter;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * 处理分类导入消息
     *
     * @param OperationInterface $operation
     * @return void
     */
    public function process(OperationInterface $operation): void
    {
        $startTime = microtime(true);
        $categoryId = 'unknown';
        $status = OperationInterface::STATUS_TYPE_COMPLETE;
        $errorCode = null;
        $message = null;
        
        try {
            // 从 Operation 中获取序列化的数据并反序列化
            $serializedData = $operation->getSerializedData();
            $categoryData = $this->serializer->unserialize($serializedData);

            // 验证必填字段：id
            if (empty($categoryData['id'])) {
                $this->logger->warning('Invalid category data received: missing ID', [
                    'data' => $categoryData
                ]);
                throw new \InvalidArgumentException('Category ID is required');
            }

            // 验证必填字段：name
            if (empty($categoryData['name'])) {
                $this->logger->warning('Invalid category data received: missing name', [
                    'data' => $categoryData,
                    'id' => $categoryData['id']
                ]);
                throw new \InvalidArgumentException('Category name is required');
            }

            $categoryId = $categoryData['id'];

            $this->logger->info('Processing category import', [
                'category_id' => $categoryId,
                'name' => $categoryData['name']
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
            
            // 状态保持为 COMPLETE
            
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
            
            // 设置为可重试失败状态
            $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // ❌ 业务逻辑异常，不可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process category import (business error)', [
                'category_id' => $categoryId ?? 'unknown',
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            
        } catch (\Exception $e) {
            // ❌ 其他错误，不可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process category import', [
                'category_id' => $categoryId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during category import. Please see log for details.');
        }

        // ✅ 关键：更新 Operation 状态并保存到数据库
        $operation->setStatus($status)
            ->setErrorCode($errorCode)
            ->setResultMessage($message);

        $this->entityManager->save($operation);
    }
}
