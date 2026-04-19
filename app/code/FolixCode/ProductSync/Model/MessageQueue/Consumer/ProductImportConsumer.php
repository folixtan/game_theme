<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Service\ProductImporter;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
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

    public function __construct(
        ProductImporter $productImporter,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->productImporter = $productImporter;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * 处理产品导入消息
     *
     * @param OperationInterface $operation
     * @return void
     * @throws \Exception 如果处理失败，抛出异常让 Magento 框架处理重试
     */
    public function process(OperationInterface $operation): void
    {
        $startTime = microtime(true);
        $productData = [];
        
        try {
            // 从 Operation 中获取序列化的数据并反序列化
            $serializedData = $operation->getSerializedData();
            $productsData = $this->serializer->unserialize($serializedData);

            // 验证必填字段：id
            if (empty($productData)) {
                return;
            }

           

            $this->logger->info('Processing product import', $productsData);

            // 执行导入
           $productIds =   $this->productImporter->importBatch($productData);
              $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Product import completed successfully', $productIds);
         

        }  catch (\Exception $e) {
            // ❌ 其他所有错误：记录日志并抛出异常
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process product import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);
            
            // 抛出异常，让 Magento 框架自动处理重试逻辑
           throw $e;
        }
    }
}
