<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Service\ProductImporter;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * 产品导入消费者
 */
class ProductImportConsumer
{
    private ProductImporter $productImporter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private EntityManager $entityManager;

    public function __construct(
        ProductImporter $productImporter,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        EntityManager $entityManager
    ) {
        $this->productImporter = $productImporter;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * 处理产品导入消息
     *
     * @param OperationInterface $operation
     * @return void
     */
    public function process(OperationInterface $operation): void
    {
        $startTime = microtime(true);
        $productId = 'unknown';
        $status = OperationInterface::STATUS_TYPE_COMPLETE;
        $errorCode = null;
        $message = null;
        
        try {
            // 从 Operation 中获取序列化的数据并反序列化
            $serializedData = $operation->getSerializedData();
            $productData = $this->serializer->unserialize($serializedData);

            // 验证必填字段：id
            if (empty($productData['id'])) {
                $this->logger->warning('Invalid product data received: missing ID', [
                    'data' => $productData
                ]);
                throw new \InvalidArgumentException('Product ID is required');
            }

            $productId = $productData['id'];

            $this->logger->info('Processing product import', [
                'product_id' => $productId
            ]);

            $this->productImporter->import($productData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Product import completed successfully', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);

        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            // ✅ 产品已存在，视为成功
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Product already exists, skipped', [
                'product_id' => $productId ?? 'unknown',
                'duration_ms' => $duration
            ]);
            
            // 状态保持为 COMPLETE
            
        } catch (\Magento\Framework\DB\Adapter\LockWaitException | 
                 \Magento\Framework\DB\Adapter\DeadlockException |
                 \Magento\Framework\DB\Adapter\ConnectionException $e) {
            // ✅ 数据库锁等待/死锁，可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->warning('Database lock detected, will retry', [
                'product_id' => $productId ?? 'unknown',
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
            $this->logger->critical('Failed to process product import (business error)', [
                'product_id' => $productId ?? 'unknown',
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            
        } catch (\Exception $e) {
            // ❌ 其他错误，不可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process product import', [
                'product_id' => $productId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during product import. Please see log for details.');
        }

        // ✅ 关键：更新 Operation 状态并保存到数据库
        $operation->setStatus($status)
            ->setErrorCode($errorCode)
            ->setResultMessage($message);

        $this->entityManager->save($operation);
    }
}
