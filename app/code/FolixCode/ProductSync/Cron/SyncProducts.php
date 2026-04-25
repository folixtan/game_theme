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
     * 保存最后同步页码配置
     *
     * @param int $page
     * @return void
     */
    private function saveLastSyncPage(int $page): void
    {
        try {
            $this->configResource->saveConfig(
                ProductSyncHelper::XML_PATH_LAST_SYNC_PAGE,
                $page,
                ScopeInterface::SCOPE_STORE,
                0
            );
            
            $this->logger->info('ProductSync last sync page updated', [
                'page' => $page
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save last sync page', [
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * 执行产品同步任务
     * 
     * 流程：
     * 1. 从上次中断的页码继续同步（断点续传）
     * 2. 从外部 API 获取产品列表
     * 3. 将每个产品发布到消息队列
     * 4. Consumer 会异步处理导入
     * 5. 每页处理完后更新页码配置
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            // 检查是否启用
            if (!$this->productSyncHelper->isEnabled()) {
                $this->logger->info('ProductSync Products Cron: Synchronization is disabled, skipping.');
                return;
            }

            $this->logger->info('ProductSync Products Cron: Starting product synchronization...');

            $perPage = $this->productSyncHelper->getBatchSize();

            // ✅ 从配置中读取上次同步的页码（断点续传）
            $page = $this->productSyncHelper->getLastSyncPage();
            $lastPage = 1; // 初始值，会在第一次API调用后更新
            $totalPublished = 0;
            $pagesProcessed = 0;

            $this->logger->info('ProductSync Products Cron: Resuming from page', [
                'start_page' => $page
            ]);

            while ($page <= $lastPage) {
                try {
                    // 从 API 获取产品列表
                    $productsData = $this->apiService->getProductList([
                        'page' => $page,
                        'per_page' => $perPage,
                        'timestamp' => $this->timezone->date()->getTimestamp(),
                    ]);

                    if (empty($productsData['data'])) {
                        $this->logger->info('ProductSync Products Cron: No products on page', ['page' => $page]);
                        break;
                    }

                    // 更新总页数（只在第一次获取）
                    if ($pagesProcessed === 0 && isset($productsData['last_page']) && $productsData['last_page'] > 0) {
                        $lastPage = (int) $productsData['last_page'];
                        $this->logger->info('ProductSync Products Cron: Total pages fetched', [
                            'total_pages' => $lastPage
                        ]);
                    }

                    $this->logger->info('ProductSync Products Cron: Fetched products from API', [
                        'current_page' => $page,
                        'total_pages' => $lastPage,
                        'count' => count($productsData['data'])
                    ]);

                    // 将产品数据发布到消息队列（异步导入）
                    $this->publisher->publishProductImport($productsData['data']);
                    $totalPublished += count($productsData['data']);
                    $pagesProcessed++;

                    // ✅ 每页处理完后，立即更新页码配置（断点续传关键点）
                    $this->saveLastSyncPage($page + 1);

                    $page++;

                } catch (\Exception $e) {
                    $this->logger->error('ProductSync Products Cron: Failed to fetch products', [
                        'page' => $page,
                        'error' => $e->getMessage()
                    ]);
                    // ❌ 失败时不更新页码，下次Cron会从当前页重试
                    break;
                }
            }

            $this->logger->info('ProductSync Products Cron: Synchronization completed', [
                'total_published' => $totalPublished,
                'pages_processed' => $pagesProcessed,
                'next_start_page' => $page
            ]);

            // ✅ 如果所有页都处理完了，重置页码为1（下一轮从头开始）
            if ($page > $lastPage && $lastPage > 0) {
                $this->saveLastSyncPage($lastPage);
                $this->logger->info('ProductSync Products Cron: All pages processed, resetting to page 1');
                
                
            }

        } catch (\Exception $e) {
            $this->logger->error('ProductSync Products Cron: Product synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}