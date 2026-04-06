<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\ProductSync\Api\VirtualGoodsApiInterface;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 虚拟商品API服务 - 业务层实现
 * 实现语雀文档中的虚拟商品API接口
 */
class VirtualGoodsApiService implements VirtualGoodsApiInterface
{
    private ExternalApiClientInterface $apiClient;
    private LoggerInterface $logger;
    private LoggerInterface $apiLogger;

    // API端点配置
    private const PRODUCT_LIST_ENDPOINT = '/api/user-goods/list';
    private const CATEGORY_LIST_ENDPOINT = '/api/user-goods/category';
    private const PRODUCT_DETAIL_ENDPOINT = '/api/user-goods/detail';

    public function __construct(
        ExternalApiClientInterface $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;

        // 创建独立的API日志记录器
        $this->apiLogger = new Logger('virtual_goods_api');
        $logPath = BP . '/var/log/virtual_goods_api.log';
        $this->apiLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    /**
     * @inheritdoc
     */
    public function getProductList(int $limit = 100, int $page = 1, int $timestamp = 0): array
    {
        try {
            $url = $this->apiClient->getApiBaseUrl() . self::PRODUCT_LIST_ENDPOINT;
            $params = [
                'limit' => $limit,
                'page' => $page
            ];

            // 增量同步：添加时间戳参数
            if ($timestamp > 0) {
                $params['timestamp'] = $timestamp;
            }

            $this->apiLogger->info('Fetching product list', ['url' => $url, 'params' => $params]);

            $response = $this->apiClient->post($url, $params);
var_dump($response);exit;
            $this->apiLogger->info('Product list response received', ['response' => $response]);

            // 验证响应格式
            if (!isset($response['items']) && !isset($response['data'])) {
                $errorMsg = 'Invalid product list response format';
                $this->apiLogger->error($errorMsg, ['response' => $response]);
                $this->logger->error($errorMsg, ['response' => $response]);
                throw new \RuntimeException('Invalid API response format for product list');
            }

            $items = $response['items'] ?? $response['data'] ?? [];

            $this->apiLogger->info('Successfully fetched products from external API', ['count' => count($items)]);
            $this->logger->info(sprintf('Successfully fetched %d products from external API', count($items)));

            return $items;

        } catch (\Exception $e) {
            $this->apiLogger->error('Failed to fetch product list', ['error' => $e->getMessage()]);
            $this->logger->error('Failed to fetch product list: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function getCategoryList(int $timestamp = 0): array
    {
        try {
            $url = $this->apiClient->getApiBaseUrl() . self::CATEGORY_LIST_ENDPOINT;
            $params = [];

            // 增量同步：添加时间戳参数
            if ($timestamp > 0) {
                $params['timestamp'] = $timestamp;
            }

            $this->apiLogger->info('Fetching category list', ['url' => $url, 'params' => $params]);

            $response = $this->apiClient->post($url, $params);

            $this->apiLogger->info('Category list response received', ['response' => $response]);

            // 验证响应格式
            if (!is_array($response)) {
                $errorMsg = 'Invalid category list response format';
                $this->apiLogger->error($errorMsg, ['response' => $response]);
                $this->logger->error($errorMsg, ['response' => $response]);
                throw new \RuntimeException('Invalid API response format for category list');
            }

            $categories = is_array($response) ? $response : [];

            $this->apiLogger->info('Successfully fetched categories from external API', ['count' => count($categories)]);
            $this->logger->info(sprintf('Successfully fetched %d categories from external API', count($categories)));

            return $categories;

        } catch (\Exception $e) {
            $this->apiLogger->error('Failed to fetch category list', ['error' => $e->getMessage()]);
            $this->logger->error('Failed to fetch category list: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function getProductDetail(string $productId): array
    {
        try {
            $url = $this->apiClient->getApiBaseUrl() . self::PRODUCT_DETAIL_ENDPOINT . '/' . $productId;

            $this->apiLogger->info('Fetching product detail', ['url' => $url, 'product_id' => $productId]);

            $response = $this->apiClient->get($url);

            $this->apiLogger->info('Product detail response received', ['response' => $response]);

            // 验证响应格式
            if (!is_array($response) || empty($response)) {
                $errorMsg = 'Invalid product detail response format';
                $this->apiLogger->error($errorMsg, ['response' => $response]);
                $this->logger->error($errorMsg, ['response' => $response]);
                throw new \RuntimeException('Invalid API response format for product detail');
            }

            $this->apiLogger->info('Successfully fetched product detail', ['product_id' => $productId]);
            $this->logger->info(sprintf('Successfully fetched product detail for ID: %s', $productId));

            return $response;

        } catch (\Exception $e) {
            $this->apiLogger->error('Failed to fetch product detail', ['product_id' => $productId, 'error' => $e->getMessage()]);
            $this->logger->error(sprintf('Failed to fetch product detail for ID %s: %s', $productId, $e->getMessage()));
            throw $e;
        }
    }
}