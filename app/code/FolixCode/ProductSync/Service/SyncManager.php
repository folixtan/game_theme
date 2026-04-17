<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

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
    private LoggerInterface $syncLogger;
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

        // 创建独立的同步日志记录器
        $this->syncLogger = new Logger('sync_manager');
        $logPath = BP . '/var/log/product_sync.log';
        $this->syncLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
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

        $this->syncLogger->info('Starting sync', ['type' => $type]);
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

            $this->syncLogger->info('Sync completed', ['results' => $results]);
            $this->logger->info('ProductSync Manager: Sync completed', [
                'results' => $results
            ]);

        } catch (\Exception $e) {
            $results['failed'][$type] = $e->getMessage();
            $this->syncLogger->error('Sync failed', [
                'error' => $e->getMessage()
            ]);
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
     * @param array $params
     * @return int
     */
    private function syncProducts(array $params): int
    {
        $limit = $params['limit'] ?? 100;
        $page = $params['page'] ?? 1;
        $timestamp = $params['timestamp'] ?: $this->timezone->date()->getTimestamp();

        $this->syncLogger->info('Syncing products', [
            'limit' => $limit,
            'page' => $page,
            'timestamp' => $timestamp ,
        ]);
        $this->logger->info('Syncing products', [
            'limit' => $limit,
            'page' => $page,
            'timestamp' => $timestamp ,
        ]);

        // 获取商品列表
        $products = $this->apiService->getProductList($limit, $page, $timestamp);

        // 发布到消息队列或直接处理
        foreach ($products as $product) {
            try {
                $this->messagePublisher->publishProductImport($product);
            } catch (\Exception $e) {
                $this->syncLogger->error('Failed to publish product', [
                    'product_id' => $product['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $this->logger->error('Failed to publish product', [
                    'product_id' => $product['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->syncLogger->info('Products sync completed', ['count' => count($products)]);
        $this->logger->info('Products sync completed', ['count' => count($products)]);

        return count($products);
    }

    /**
     * 同步分类列表
     *
     * @param array $params
     * @return int
     */
    private function syncCategories(array $params): int
    {
        $timestamp = $params['timestamp'] ?: $this->timezone->date()->getTimestamp();

        $this->syncLogger->info('Syncing categories', [
            'timestamp' => $timestamp ?: $this->timezone->date()->getTimestamp()
        ]);
        $this->logger->info('Syncing categories', [
            'timestamp' => $timestamp ?: $this->timezone->date()->getTimestamp()
        ]);

        // 获取分类列表
        $categories = $this->apiService->getCategoryList($timestamp);

        // 发布到消息队列或直接处理
        foreach ($categories as $category) {
            try {
                $this->messagePublisher->publishCategoryImport($category);
            } catch (\Exception $e) {
                $this->syncLogger->error('Failed to publish category', [
                    'category_id' => $category['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $this->logger->error('Failed to publish category', [
                    'category_id' => $category['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->syncLogger->info('Categories sync completed', ['count' => count($categories)]);
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
        $this->syncLogger->info('Syncing product detail', ['product_id' => $productId]);
        $this->logger->info('Syncing product detail', ['product_id' => $productId]);

        try {
            $this->messagePublisher->publishProductDetail($productId);
            $this->syncLogger->info('Product detail sync initiated', ['product_id' => $productId]);
            $this->logger->info('Product detail sync initiated', ['product_id' => $productId]);
        } catch (\Exception $e) {
            $this->syncLogger->error('Failed to sync product detail', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
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