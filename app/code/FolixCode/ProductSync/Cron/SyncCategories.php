<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\ProductSync\Helper\Data as ProductSyncHelper;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;

/**
 * Cron任务 - 定时同步分类数据（独立任务）
 * 
 * 职责：从 API 获取数据并发布到消息队列
 * 注意：实际的导入工作由 Consumer 异步完成
 */
class SyncCategories
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
     * 执行分类同步任务
     * 
     * 流程：
     * 1. 从外部 API 获取分类列表
     * 2. 将每个分类发布到消息队列
     * 3. Consumer 会异步处理导入
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            // 检查是否启用
            if (!$this->baseHelper->isEnabled()) {
                $this->logger->info('ProductSync Categories Cron: Synchronization is disabled, skipping.');
                return;
            }

            $this->logger->info('ProductSync Categories Cron: Starting category synchronization...');

            // 获取最后一次同步时间戳（增量同步）- 使用业务 Helper
            $lastSyncTimestamp = $this->productSyncHelper->getLastSyncTimestamp();

            // 1. 从 API 获取分类列表
            $categoriesData = $this->apiService->getCategoryList([
                'timestamp' => $lastSyncTimestamp
            ]);

            if (empty($categoriesData)) {
                $this->logger->info('ProductSync Categories Cron: No categories to sync.');
                return;
            }

            $this->logger->info('ProductSync Categories Cron: Fetched categories from API', [
                'count' => count($categoriesData)
            ]);

            // 2. 将每个分类发布到消息队列（异步导入）
            $publishedCount = 0;
            foreach ($categoriesData as $categoryData) {
                try {
                    $this->publisher->publishCategoryImport($categoryData);
                    $publishedCount++;
                } catch (\Exception $e) {
                    $this->logger->error('ProductSync Categories Cron: Failed to publish category to MQ', [
                        'category_id' => $categoryData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->info('ProductSync Categories Cron: Categories published to message queue', [
                'total_fetched' => count($categoriesData),
                'published_to_mq' => $publishedCount,
                'note' => 'Actual import will be handled by Consumer asynchronously'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ProductSync Categories Cron: Category synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}