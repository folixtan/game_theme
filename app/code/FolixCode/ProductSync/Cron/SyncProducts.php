<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\ProductSync\Helper\Data as ProductSyncHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\CacheInterface;

/**
 * Cron任务 - 定时全量同步产品数据
 *
 * 流程：name → product_type → page
 * - 外层遍历 name 关键词（逗号分隔，可选）
 * - 中层遍历 product_type（3=卡密, 4=直充）
 * - 内层分页拉取，每个 (name, product_type) 组合独立分页
 * - 断点续传通过 Cache 实现（不写 config）
 */
class SyncProducts
{
    private const CACHE_KEY_PREFIX = 'product_sync_page_';
    private const CACHE_LIFETIME = 86400; // 24小时

    private VirtualGoodsApiService $apiService;
    private PublisherInterface $publisher;
    private ProductSyncHelper $productSyncHelper;
    private LoggerInterface $logger;
    private TimezoneInterface $timezone;
    private CacheInterface $cache;

    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $publisher,
        ProductSyncHelper $productSyncHelper,
        TimezoneInterface $timezone,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->publisher = $publisher;
        $this->productSyncHelper = $productSyncHelper;
        $this->timezone = $timezone;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            if (!$this->productSyncHelper->isEnabled()) {
                $this->logger->info('ProductSync Cron: disabled, skipping.');
                return;
            }

            $perPage = $this->productSyncHelper->getBatchSize();
            $productTypes = $this->productSyncHelper->getProductTypes();
            $goodsCategoryId = $this->productSyncHelper->getGoodsCategoryId();
            $names = $this->productSyncHelper->getSyncNames();

         
            if(empty($names)) {
                $this->logger->info('ProductSync Cron: no name configured, skipping.');
                return;
            }
           
            $totalPublished = 0;
            $totalPages = 0;
          
            $this->logger->info('ProductSync Cron: starting full sync', [
                'names' => $names,
                'product_types' => $productTypes,
                'goods_category_id' => $goodsCategoryId ?: 'all',
                'per_page' => $perPage
            ]);

            foreach ($names as $name) {
                $name = trim($name);

                foreach ($productTypes as $productType) {
                    $cacheKey = self::CACHE_KEY_PREFIX . $name . '_' . $productType;

                    // 检查缓存，决定从哪一页开始
                    $cachedPage = $this->cache->load($cacheKey);
                    $page = $cachedPage ? (int)$cachedPage : 1;
                    $lastPage = $page;

                    $this->logger->info('ProductSync Cron: syncing (name, type)', [
                        'name' => $name ?: '(all)',
                        'product_type' => $productType,
                        'start_page' => $page,
                        'from_cache' => $cachedPage !== false
                    ]);

                    while ($page <= $lastPage) {
                        try {
                            $apiParams = [
                                'page' => $page,
                                'name' => $name,
                                'per_page' => $perPage,
                                'timestamp' => $this->timezone->date()->getTimestamp(),
                                'product_type' => $productType,
                            ];
                            
                            if ($goodsCategoryId !== null) {
                                $apiParams['goods_category_id'] = $goodsCategoryId;
                            }

                            $productsData = $this->apiService->getProductList($apiParams);

                            if (empty($productsData['data'])) {
                                $this->logger->info('ProductSync Cron: no data, next type', [
                                    'name' => $name ?: '(all)',
                                    'product_type' => $productType,
                                    'page' => $page
                                ]);
                                break;
                            }

                            // 始终用 API 返回的 last_page 覆盖
                            if (!empty($productsData['last_page'])) {
                                $lastPage = (int)$productsData['last_page'];
                            }

                            $count = count($productsData['data']);
                            $this->publisher->publishProductImport($productsData['data']);
                            $totalPublished += $count;
                            $totalPages++;

                            $this->logger->info(sprintf(
                                'ProductSync Cron: page %d/%d published %d products',
                                $page, $lastPage, $count
                            ));

                            $page++;

                            // 每页成功后保存当前页，下次 cron 从此继续
                            $this->cache->save(
                                (string)$page,
                                $cacheKey,
                                [],
                                self::CACHE_LIFETIME
                            );

                        } catch (\Exception $e) {
                            $this->logger->error('ProductSync Cron: failed on page', [
                                'name' => $name ?: '(all)',
                                'product_type' => $productType,
                                'page' => $page,
                                'error' => $e->getMessage()
                            ]);
                            break;
                        }
                    }

                    $this->logger->info('ProductSync Cron: type done', [
                        'name' => $name,
                        'product_type' => $productType,
                        'last_page' => $lastPage,
                        'pages_synced' => $page - 1
                    ]);
                }
            }

            $this->logger->info(sprintf(
                'ProductSync Cron: done. %d products published across %d pages',
                $totalPublished, $totalPages
            ));

        } catch (\Exception $e) {
            $this->logger->error('ProductSync Cron: fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}