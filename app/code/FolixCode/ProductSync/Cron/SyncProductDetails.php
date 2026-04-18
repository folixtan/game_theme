<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Cron任务 - 定时同步产品详情数据（独立任务）
 * 
 * 职责：获取需要同步详情的产品列表并发布到消息队列
 * 注意：实际的详情导入工作由 Consumer 异步完成
 */
class SyncProductDetails
{
    private PublisherInterface $publisher;
    private BaseHelper $baseHelper;
    private ProductCollectionFactory $productCollectionFactory;
    private LoggerInterface $logger;

    // 每次同步的产品数量限制
    private const BATCH_SIZE = 50;

    public function __construct(
        PublisherInterface $publisher,
        BaseHelper $baseHelper,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->baseHelper = $baseHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * 执行产品详情同步任务
     * 
     * 流程：
     * 1. 查询需要同步详情的本地产品
     * 2. 将每个产品ID发布到消息队列
     * 3. Consumer 会调用 API 获取详情并导入
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            // 检查是否启用
            if (!$this->baseHelper->isEnabled()) {
                $this->logger->info('ProductSync Details Cron: Synchronization is disabled, skipping.');
                return;
            }

            $this->logger->info('ProductSync Details Cron: Starting product details synchronization...');

            // 获取需要同步详情的产品列表（例如：最近更新的虚拟产品）
            $productIds = $this->getProductsToSync();

            if (empty($productIds)) {
                $this->logger->info('ProductSync Details Cron: No products need detail sync.');
                return;
            }

            $publishedCount = 0;
            $failedCount = 0;

            // 将每个产品ID发布到消息队列
            foreach ($productIds as $productId) {
                try {
                    // 提取外部产品ID（去掉 'vg_' 前缀）
                    $externalProductId = str_replace('vg_', '', $productId);
                    
                    // 发布到 MQ，Consumer 会负责调用 API 获取详情
                    $this->publisher->publishProductDetail($externalProductId);
                    $publishedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->logger->warning('ProductSync Details Cron: Failed to publish product detail to MQ', [
                        'product_id' => $productId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->info('ProductSync Details Cron: Product details published to message queue', [
                'total' => count($productIds),
                'published_to_mq' => $publishedCount,
                'failed' => $failedCount,
                'note' => 'Consumer will fetch details from API and import'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ProductSync Details Cron: Product details synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 获取需要同步详情的产品列表
     *
     * @return array SKU数组
     */
    private function getProductsToSync(): array
    {
        try {
            // 查询最近更新的虚拟产品（示例逻辑，可根据实际需求调整）
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToFilter('type_id', 'virtual')
                ->addAttributeToFilter('sku', ['like' => 'vg_%'])
                ->setOrder('updated_at', 'DESC')
                ->setPageSize(self::BATCH_SIZE);

            $productIds = [];
            foreach ($collection as $product) {
                $productIds[] = $product->getSku();
            }

            return $productIds;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get products for detail sync', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}