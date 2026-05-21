<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue;

use FolixCode\ProductSync\Api\Message\PublisherInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\MessageQueue\PublisherInterface as MqPublisher;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Bulk\OperationInterface;

/**
 * 消息队列发布者实现 - 使用 OperationInterface 标准方式
 */
class Publisher implements PublisherInterface
{
    private MqPublisher $mqPublisher;
    private OperationInterfaceFactory $operationFactory;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private LoggerInterface $publisherLogger;
    private array $excludedProductIds;

    // Topic名称
    private const TOPIC_PRODUCT_IMPORT = 'folixcode.product.import';
    private const TOPIC_CATEGORY_IMPORT = 'folixcode.category.import';

    public function __construct(
        MqPublisher $mqPublisher,
        OperationInterfaceFactory $operationFactory,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        LoggerInterface $publisherLogger
    ) {
        $this->mqPublisher = $mqPublisher;
        $this->operationFactory = $operationFactory;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->publisherLogger = $publisherLogger;
        $this->excludedProductIds = (array) include __DIR__ . '/../Config/ExcludedProducts.php';
    }

    /**
     * @inheritdoc
     */
    public function publishProductImport(array $productData): void
    {
        try {
            // 过滤掉排除列表中的产品
            $excludedIds = $this->excludedProductIds;
            $beforeCount = count($productData);
            $productData = array_filter($productData, function (array $item) use ($excludedIds): bool {
                return !in_array((string) ($item['product_id'] ?? ''), $excludedIds, true);
            });

            $filteredCount = $beforeCount - count($productData);
            if ($filteredCount > 0) {
                $this->publisherLogger->info(sprintf(
                    'Product import: %d products excluded, %d remaining',
                    $filteredCount,
                    count($productData)
                ));
            }

            if (empty($productData)) {
                return;
            }

            // 创建 Operation 对象（符合 Magento 官方标准）
            $operation = $this->operationFactory->create([
                'data' => [
                    'topic_name' => self::TOPIC_PRODUCT_IMPORT,
                    'serialized_data' => $this->serializer->serialize(array_values($productData)),
                    'status' => OperationInterface::STATUS_TYPE_OPEN
                ]
            ]);
            
            // 发布 Operation
            $this->mqPublisher->publish(self::TOPIC_PRODUCT_IMPORT, $operation);
            
            $this->publisherLogger->info('Product import message published', $productData);
           // $this->logger->debug('Product import message published', ['product_id' => $productData['id'] ?? 'unknown']);
        } catch (\Exception $e) {
            $this->publisherLogger->error('Failed to publish product import message', [
                'error' => $e->getMessage(),
                'data' => $productData
            ]);
            $this->logger->error('Failed to publish product import message', [
                'error' => $e->getMessage(),
                'data' => $productData
            ]);
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function publishCategoryImport(array $categoryData): void
    {
        try {
            // 创建 Operation 对象（符合 Magento 官方标准）
            $operation = $this->operationFactory->create([
                'data' => [
                    'topic_name' => self::TOPIC_CATEGORY_IMPORT,
                    'serialized_data' => $this->serializer->serialize($categoryData),
                    'status' => OperationInterface::STATUS_TYPE_OPEN,
                ]
            ]);
            
            // 发布 Operation
            $this->mqPublisher->publish(self::TOPIC_CATEGORY_IMPORT, $operation);
            
            $this->publisherLogger->info('Category import message published', ['category_id' => $categoryData['id'] ?? 'unknown']);
            $this->logger->debug('Category import message published', ['category_id' => $categoryData['id'] ?? 'unknown']);
        } catch (\Exception $e) {
            $this->publisherLogger->error('Failed to publish category import message', [
                'error' => $e->getMessage(),
                'data' => $categoryData
            ]);
            $this->logger->error('Failed to publish category import message', [
                'error' => $e->getMessage(),
                'data' => $categoryData
            ]);
            throw $e;
        }
    }

}
