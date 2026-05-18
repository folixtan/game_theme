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
     * 执行产品同步任务
     * 
     * 流程：
     * 1. 遍历每种产品类型
     * 2. 对每种类型从第1页开始分页获取数据
     * 3. 将每个产品发布到消息队列
     * 4. Consumer 会异步处理导入
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

            // ✅ 获取产品类型和分类ID筛选配置
            $productTypes = $this->productSyncHelper->getProductTypes();
            $goodsCategoryId = $this->productSyncHelper->getGoodsCategoryId();

            $this->logger->info('ProductSync Products Cron: Filter configuration', [
                'product_types' => $productTypes,
                'goods_category_id' => $goodsCategoryId ?: 'all'
            ]);

            $totalPublished = 0;
            $totalPagesProcessed = 0;

            // ✅ 循环处理每种产品类型（API 每次只支持单个 product_type）
            foreach ($productTypes as $productType) {
                $this->logger->info('ProductSync Products Cron: Processing product type', [
                    'product_type' => $productType
                ]);

                // 重置页码，从第1页开始
                $page = 1;
                $lastPage = 1;
                $typePublished = 0;
                $typePagesProcessed = 0;

                while (true) {
                    try {
                        // 构建 API 请求参数
                        $apiParams = [
                            'page' => $page,
                            'per_page' => $perPage,
                            'timestamp' => $this->timezone->date()->getTimestamp(),
                            'product_type' => $productType, // ✅ 每次只传一个产品类型
                        ];

                        // ✅ 添加分类ID筛选（如果有配置）
                        if ($goodsCategoryId !== null) {
                            $apiParams['goods_category_id'] = $goodsCategoryId;
                        }

                        // 从 API 获取产品列表
                        $productsData = $this->apiService->getProductList($apiParams);

                        if (empty($productsData['data'])) {
                            $this->logger->info('ProductSync Products Cron: No products on page', [
                                'product_type' => $productType,
                                'page' => $page
                            ]);
                            break;
                        }

                        // 更新总页数（只在第一次获取）
                        if ($typePagesProcessed === 0 && isset($productsData['last_page']) && $productsData['last_page'] > 0) {
                            $lastPage = (int) $productsData['last_page'];
                            $this->logger->info('ProductSync Products Cron: Total pages fetched for product type', [
                                'product_type' => $productType,
                                'total_pages' => $lastPage
                            ]);
                        }

                        $this->logger->info('ProductSync Products Cron: Fetched products from API', [
                            'product_type' => $productType,
                            'current_page' => $page,
                            'total_pages' => $lastPage,
                            'count' => count($productsData['data'])
                        ]);

                        // 将产品数据发布到消息队列（异步导入）
                        $this->publisher->publishProductImport($productsData['data']);
                        $typePublished += count($productsData['data']);
                        $typePagesProcessed++;

                        // 检查是否还有下一页
                        if ($page >= $lastPage) {
                            break;
                        }

                        $page++;

                    } catch (\Exception $e) {
                        $this->logger->error('ProductSync Products Cron: Failed to fetch products', [
                            'product_type' => $productType,
                            'page' => $page,
                            'error' => $e->getMessage()
                        ]);
                        // 失败时中断当前类型的处理，继续下一个类型
                        break;
                    }
                }

                $this->logger->info('ProductSync Products Cron: Product type processing completed', [
                    'product_type' => $productType,
                    'published' => $typePublished,
                    'pages_processed' => $typePagesProcessed
                ]);

                $totalPublished += $typePublished;
                $totalPagesProcessed += $typePagesProcessed;
            }

            $this->logger->info('ProductSync Products Cron: All product types synchronization completed', [
                'total_published' => $totalPublished,
                'total_pages_processed' => $totalPagesProcessed,
                'product_types_count' => count($productTypes)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ProductSync Products Cron: Product synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}