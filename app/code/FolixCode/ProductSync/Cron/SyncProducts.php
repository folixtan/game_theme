<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\ProductSync\Helper\Data as ProductSyncHelper;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

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

    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $publisher,
        ProductSyncHelper $productSyncHelper,
        BaseHelper $baseHelper,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->publisher = $publisher;
        $this->productSyncHelper = $productSyncHelper;
        $this->baseHelper = $baseHelper;
        $this->timezone = $timezone;
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

            $last_page = 2;
            $per_page = 10;
            $page = 1;
            $num = 0;

            while($page < $last_page) {
                   // 获取最后一次同步时间戳（增量同步）- 使用业务 Helper
                $lastSyncTimestamp = $this->timezone->date()->getTimestamp();
                $num++;
                //结束页码
                 if($num > $last_page) break;
                // 1. 从 API 获取产品列表
                $productsData = $this->apiService->getProductList([
                    'timestamp' => $lastSyncTimestamp,
                    'page' => $page,
                    'per_page' => $per_page
                ]);

                if($last_page === 0) $last_page = $productsData['last_page'];

            }
          
            if (empty($productsData['data'])) {
                $this->logger->info('ProductSync Products Cron: No products to sync.');
                return;
            }

            $this->logger->info('ProductSync Products Cron: Fetched products from API', [
                'count' => count($productsData['data'])
            ]);

            // 2. 将每个产品发布到消息队列（异步导入）
            $publishedCount = 0;
     
            try {
                $this->publisher->publishProductImport($productsData['data']);
                $publishedCount++;
            } catch (\Exception $e) {
                $this->logger->error('ProductSync Products Cron: Failed to publish product to MQ', [
                    'product_id' => $productData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        

            $this->logger->info('ProductSync Products Cron: Products published to message queue', [
                'total_fetched' => count($productsData),
                'published_to_mq' => $publishedCount,
                'note' => 'Actual import will be handled by Consumer asynchronously'
            ]);
          $page++;
        } catch (\Exception $e) {
            $this->logger->error('ProductSync Products Cron: Product synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}