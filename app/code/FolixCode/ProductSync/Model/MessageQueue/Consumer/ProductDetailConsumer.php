<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Service\ProductDetailImporter;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * 产品详情消费者
 */
class ProductDetailConsumer
{
    private ProductDetailImporter $productDetailImporter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private EntityManager $entityManager;

    public function __construct(
        ProductDetailImporter $productDetailImporter,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        EntityManager $entityManager
    ) {
        $this->productDetailImporter = $productDetailImporter;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * 处理产品详情消息
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
            $data = $this->serializer->unserialize($serializedData);

            $productId = $data['product_id'] ?? null;

            // 验证必填字段：product_id
            if (!$productId) {
                $this->logger->warning('Invalid product detail data received: missing product_id', [
                    'data' => $data
                ]);
                throw new \InvalidArgumentException('Product ID is required');
            }

            $this->logger->info('Processing product detail import', [
                'product_id' => $productId
            ]);

            $this->productDetailImporter->import($productId);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Product detail import completed', [
                'product_id' => $productId,
                'duration_ms' => $duration
            ]);

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
            $this->logger->critical('Failed to process product detail import (business error)', [
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
            $this->logger->critical('Failed to process product detail import', [
                'product_id' => $productId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during product detail import. Please see log for details.');
        }

        // ✅ 关键：更新 Operation 状态并保存到数据库
        $operation->setStatus($status)
            ->setErrorCode($errorCode)
            ->setResultMessage($message);

        $this->entityManager->save($operation);
    }
}