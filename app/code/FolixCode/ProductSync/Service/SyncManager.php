<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * 产品同步管理器 - 业务层核心
 * 协调API调用、消息发布和数据处理
 */
class SyncManager
{
    private VirtualGoodsApiService $apiService;
    private PublisherInterface $messagePublisher;
    private BaseHelper $baseHelper;
    private LoggerInterface $logger;
    private TimezoneInterface $timezone;

    // 同步类型常量
    public const SYNC_TYPE_PRODUCTS = 'products';
    public const SYNC_TYPE_CATEGORIES = 'categories';
    public const SYNC_TYPE_ALL = 'all';

    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $messagePublisher,
        BaseHelper $baseHelper,
        LoggerInterface $logger,
        TimezoneInterface $timezone
    ) {
        $this->apiService = $apiService;
        $this->messagePublisher = $messagePublisher;
        $this->baseHelper = $baseHelper;
        $this->logger = $logger;
        $this->timezone = $timezone;
    }

    /**
     * 执行同步
     *
     * @param string $type 同步类型 (products, categories, all)
     * @param array $params 参数
     * @return array
     */
    public function sync(string $type = self::SYNC_TYPE_ALL, array $params = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => 0
        ];

        $this->logger->info('ProductSync Manager: Starting sync', ['type' => $type]);

        try {
            // 同步商品列表
            if ($type === self::SYNC_TYPE_ALL || $type === self::SYNC_TYPE_PRODUCTS) {
                $productCount = $this->syncProducts($params);
                $results['success'][self::SYNC_TYPE_PRODUCTS] = $productCount;
                $results['total'] += $productCount;
            }

            // 同步分类列表
            if ($type === self::SYNC_TYPE_ALL || $type === self::SYNC_TYPE_CATEGORIES) {
                $categoryCount = $this->syncCategories($params);
                $results['success'][self::SYNC_TYPE_CATEGORIES] = $categoryCount;
                $results['total'] += $categoryCount;
            }

            $this->logger->info('ProductSync Manager: Sync completed', [
                'results' => $results
            ]);

        } catch (\Exception $e) {
            $results['failed'][$type] = $e->getMessage();
            $this->logger->error('ProductSync Manager: Sync failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * 同步商品列表
     *
     * @param array $params 同步参数（数组方式，可扩展）
     * @return int
     */
    private function syncProducts(array $params = []): int
    {
        // 默认参数
        $defaultParams = [
            'limit' => 100,
            'page' => 1,
            'timestamp' => 0
        ];

        // 合并参数
        $apiParams = array_merge($defaultParams, $params);

        $this->logger->info('Syncing products', $apiParams);

        // 获取商品列表（传入数组参数）
        $products = $this->apiService->getProductList($apiParams);

        // 发布到消息队列
        foreach ($products as $product) {
            try {
                $this->messagePublisher->publishProductImport($product);
            } catch (\Exception $e) {
                $this->logger->error('Failed to publish product', [
                    'product_id' => $product['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Products sync completed', ['count' => count($products)]);

        return count($products);
    }

    /**
     * 同步分类列表
     *
     * @param array $params 同步参数（数组方式，可扩展）
     * @return int
     */
    private function syncCategories(array $params = []): int
    {
        // 默认参数
        $defaultParams = [
            'timestamp' => 0
        ];

        // 合并参数
        $apiParams = array_merge($defaultParams, $params);

        $this->logger->info('Syncing categories', $apiParams);

        // 获取分类列表（传入数组参数）
        $categories = $this->apiService->getCategoryList($apiParams);

        // 发布到消息队列
        foreach ($categories as $category) {
            try {
                $this->messagePublisher->publishCategoryImport($category);
            } catch (\Exception $e) {
                $this->logger->error('Failed to publish category', [
                    'category_id' => $category['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Categories sync completed', ['count' => count($categories)]);

        return count($categories);
    }

    /**
     * 同步单个商品详情
     *
     * @param string $productId
     * @return void
     */
    public function syncProductDetail(string $productId): void
    {
        $this->logger->info('Syncing product detail', ['product_id' => $productId]);

        try {
            $this->messagePublisher->publishProductDetail($productId);
            $this->logger->info('Product detail sync initiated', ['product_id' => $productId]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync product detail', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取可用的同步类型
     *
     * @return array
     */
    public function getAvailableSyncTypes(): array
    {
        return [
            self::SYNC_TYPE_PRODUCTS,
            self::SYNC_TYPE_CATEGORIES,
            self::SYNC_TYPE_ALL
        ];
    }
}