<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\ProductSync\Helper\Data as ProductSyncHelper;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;

/**
 * Cron任务 - 定时同步产品数据（独立任务）
 * 
 * 职责：从 API 获取数据并发布到消息队列
 * 注意：实际的导入工作由 Consumer 异步完成
 */
class SyncProducts
{
    private VirtualGoodsApiService $apiService;
    private PublisherInterface $publisher;
    private ProductSyncHelper $productSyncHelper;
    private BaseHelper $baseHelper;
    private LoggerInterface $logger;

    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $publisher,
        ProductSyncHelper $productSyncHelper,
        BaseHelper $baseHelper,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->publisher = $publisher;
        $this->productSyncHelper = $productSyncHelper;
        $this->baseHelper = $baseHelper;
        $this->logger = $logger;
    }

    /**
     * 执行产品同步任务
     * 
     * 流程：
     * 1. 从外部 API 获取产品列表
     * 2. 将每个产品发布到消息队列
     * 3. Consumer 会异步处理导入
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            // 检查是否启用
            if (!$this->baseHelper->isEnabled()) {
                $this->logger->info('ProductSync Products Cron: Synchronization is disabled, skipping.');
                return;
            }

            $this->logger->info('ProductSync Products Cron: Starting product synchronization...');

            // 获取最后一次同步时间戳（增量同步）- 使用业务 Helper
            $lastSyncTimestamp = $this->productSyncHelper->getLastSyncTimestamp();

            // 1. 从 API 获取产品列表
            $productsData = $this->apiService->getProductList([
                'timestamp' => $lastSyncTimestamp,
                'limit' => 100
            ]);

            if (empty($productsData)) {
                $this->logger->info('ProductSync Products Cron: No products to sync.');
                return;
            }

            $this->logger->info('ProductSync Products Cron: Fetched products from API', [
                'count' => count($productsData)
            ]);

            // 2. 将每个产品发布到消息队列（异步导入）
            $publishedCount = 0;
            foreach ($productsData as $productData) {
                try {
                    $this->publisher->publishProductImport($productData);
                    $publishedCount++;
                } catch (\Exception $e) {
                    $this->logger->error('ProductSync Products Cron: Failed to publish product to MQ', [
                        'product_id' => $productData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->info('ProductSync Products Cron: Products published to message queue', [
                'total_fetched' => count($productsData),
                'published_to_mq' => $publishedCount,
                'note' => 'Actual import will be handled by Consumer asynchronously'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ProductSync Products Cron: Product synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}