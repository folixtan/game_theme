<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\ProductSync\Helper\Data as ProductSyncHelper;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Store\Model\ScopeInterface;

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
    private TimezoneInterface $timezone;
    private ConfigResource $configResource;

    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $publisher,
        ProductSyncHelper $productSyncHelper,
        BaseHelper $baseHelper,
        TimezoneInterface $timezone,
        ConfigResource $configResource,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->publisher = $publisher;
        $this->productSyncHelper = $productSyncHelper;
        $this->baseHelper = $baseHelper;
        $this->timezone = $timezone;
        $this->configResource = $configResource;
        $this->logger = $logger;
    }

    /**
     * 保存最后同步时间戳配置
     *
     * @param int $timestamp
     * @return void
     */
    private function saveLastSyncTimestamp(int $timestamp): void
    {
        try {
            $this->configResource->saveConfig(
                ProductSyncHelper::XML_PATH_LAST_SYNC_TIMESTAMP,
                $timestamp,
                ScopeInterface::SCOPE_STORE,
                0
            );
            
            $this->logger->info('ProductSync last sync timestamp updated', [
                'timestamp' => $timestamp,
                'date' => date('Y-m-d H:i:s', $timestamp)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save last sync timestamp', [
                'error' => $e->getMessage()
            ]);
        }
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
            if (!$this->productSyncHelper->isEnabled()) {
                $this->logger->info('ProductSync Categories Cron: Synchronization is disabled, skipping.');
                return;
            }

            $this->logger->info('ProductSync Categories Cron: Starting category synchronization...');

            // 获取最后一次同步时间戳（增量同步）- 使用业务 Helper
            $lastSyncTimestamp = $this->productSyncHelper->getLastSyncTimestamp();

            // 1. 从 API 获取分类列表
            $categoriesData = $this->apiService->getCategoryList([
                'timestamp' => $lastSyncTimestamp >0 ?: $this->timezone->date()->getTimestamp()
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
            foreach ($categoriesData as $id => $name) {
                try {
                    $this->publisher->publishCategoryImport([
                        'id' => $id,
                        'name' => $name
                    ]);
                    $publishedCount++;
                } catch (\Exception $e) {
                    $this->logger->error('ProductSync Categories Cron: Failed to publish category to MQ', [
                        'category_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->info('ProductSync Categories Cron: Categories published to message queue', [
                'total_fetched' => count($categoriesData),
                'published_to_mq' => $publishedCount,
                'note' => 'Actual import will be handled by Consumer asynchronously'
            ]);

            // ✅ 更新最后同步时间戳
            $this->saveLastSyncTimestamp($this->timezone->date()->getTimestamp());

        } catch (\Exception $e) {
            $this->logger->error('ProductSync Categories Cron: Category synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}