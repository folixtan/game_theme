<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue;

use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\ProductSync\Api\Message\ProductImportMessageInterface;
use FolixCode\ProductSync\Api\Message\CategoryImportMessageInterface;
use FolixCode\ProductSync\Api\Message\ProductDetailMessageInterface;
use Magento\Framework\MessageQueue\PublisherInterface as MqPublisher;
use Psr\Log\LoggerInterface;

/**
 * 消息队列发布者实现
 */
class Publisher implements PublisherInterface
{
    private MqPublisher $mqPublisher;
    private ProductImportMessageInterface $productMessageFactory;
    private CategoryImportMessageInterface $categoryMessageFactory;
    private ProductDetailMessageInterface $productDetailMessageFactory;
    private LoggerInterface $logger;
    private LoggerInterface $publisherLogger;

    // Topic名称
    private const TOPIC_PRODUCT_IMPORT = 'folixcode.product.import';
    private const TOPIC_CATEGORY_IMPORT = 'folixcode.category.import';
    private const TOPIC_PRODUCT_DETAIL = 'folixcode.product.detail';

    public function __construct(
        MqPublisher $mqPublisher,
        ProductImportMessageInterface $productMessageFactory,
        CategoryImportMessageInterface $categoryMessageFactory,
        ProductDetailMessageInterface $productDetailMessageFactory,
        LoggerInterface $logger,
        LoggerInterface $publisherLogger
    ) {
        $this->mqPublisher = $mqPublisher;
        $this->productMessageFactory = $productMessageFactory;
        $this->categoryMessageFactory = $categoryMessageFactory;
        $this->productDetailMessageFactory = $productDetailMessageFactory;
        $this->logger = $logger;
        $this->publisherLogger = $publisherLogger;
    }

    /**
     * @inheritdoc
     */
    public function publishProductImport(array $productData): void
    {
        try {
            $message = $this->productMessageFactory->create($productData);
            $this->mqPublisher->publish(self::TOPIC_PRODUCT_IMPORT, $message);
            $this->publisherLogger->info('Product import message published', ['product_id' => $productData['id'] ?? 'unknown']);
            $this->logger->debug('Product import message published', ['product_id' => $productData['id'] ?? 'unknown']);
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
            $message = $this->categoryMessageFactory->create($categoryData);
            $this->mqPublisher->publish(self::TOPIC_CATEGORY_IMPORT, $message);
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

    /**
     * @inheritdoc
     */
    public function publishProductDetail(string $productId): void
    {
        try {
            $message = $this->productDetailMessageFactory->create(['product_id' => $productId]);
            $this->mqPublisher->publish(self::TOPIC_PRODUCT_DETAIL, $message);
            $this->publisherLogger->info('Product detail message published', ['product_id' => $productId]);
            $this->logger->debug('Product detail message published', ['product_id' => $productId]);
        } catch (\Exception $e) {
            $this->publisherLogger->error('Failed to publish product detail message', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            $this->logger->error('Failed to publish product detail message', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            throw $e;
        }
    }
}