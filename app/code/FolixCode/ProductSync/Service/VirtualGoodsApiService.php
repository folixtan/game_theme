<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\ProductSync\Api\VirtualGoodsApiInterface;
use Psr\Log\LoggerInterface;

/**
 * 虚拟商品API服务 - 业务层实现
 * 实现语雀文档中的虚拟商品API接口
 */
class VirtualGoodsApiService implements VirtualGoodsApiInterface
{
    private ExternalApiClientInterface $apiClient;
    private LoggerInterface $logger;

    // API端点配置
    private const PRODUCT_LIST_ENDPOINT = '/api/user-goods/list';
    private const CATEGORY_LIST_ENDPOINT = '/api/user-goods/category';
    private const PRODUCT_DETAIL_ENDPOINT = '/api/user-goods/details';

    public function __construct(
        ExternalApiClientInterface $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getProductList(array $params = []): array
    {
        try {
            // 默认参数
            $defaultParams = [
                'limit' => 100,
                'page' => 1,
                'timestamp' => 0
            ];

            // 合并参数（外部参数优先）
            $params = array_merge($defaultParams, $params);

            $url = $this->apiClient->getApiBaseUrl() . self::PRODUCT_LIST_ENDPOINT;

            $this->logger->info('Fetching product list', ['url' => $url, 'params' => $params]);

            $response = $this->apiClient->post($url, $params);
           
                 // 验证响应格式
            if ( !isset($response['data'])) {
                $errorMsg = 'Invalid product list response format';
                $this->logger->error($errorMsg, ['response' => $response]);
                throw new \RuntimeException('Invalid API response format for product list');
            }

            $this->logger->info('Product list response received', ['response_count' => count($response['data'])]);

       

            $items = $response['data'] ? $response['data'] : [];

            $this->logger->info('Successfully fetched products from external API', ['count' => count($items)]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch product list', ['error' => $e->getMessage(), 'params' => $params]);
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function getCategoryList(array $params = []): array
    {
        try {
            // 默认参数
            $defaultParams = [
                'timestamp' => 0
            ];

            // 合并参数
            $params = array_merge($defaultParams, $params);

            $url = $this->apiClient->getApiBaseUrl() . self::CATEGORY_LIST_ENDPOINT;

            $this->logger->info('Fetching category list', ['url' => $url, 'params' => $params]);

            $response = $this->apiClient->post($url, $params);

            $this->logger->info('Category list response received', ['response' => $response]);

            // 验证响应格式
            if (!is_array($response)) {
                $errorMsg = 'Invalid category list response format';
                $this->logger->error($errorMsg, ['response' => $response]);
                throw new \RuntimeException('Invalid API response format for category list');
            }

            $categories = is_array($response) ? $response : [];

            $this->logger->info('Successfully fetched categories from external API', ['count' => count($categories)]);

            return $categories;

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch category list', ['error' => $e->getMessage(), 'params' => $params]);
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function getProductDetail(array $params = []): array
    {
        try {
            if (empty($params)) {
                throw new \InvalidArgumentException('params is required');
            }

            $url = $this->apiClient->getApiBaseUrl() . self::PRODUCT_DETAIL_ENDPOINT ;

            $this->logger->info('Fetching product detail', ['url' => $url, 'params' => $params]);

            // 如果有额外参数，添加到请求中
            $response =  $this->apiClient->post($url, $params);

            $this->logger->info('Product detail response received', ['response' => $response]);

            // 验证响应格式
            if (!is_array($response) || empty($response)) {
                $errorMsg = 'Invalid product detail response format';
                $this->logger->error($errorMsg, ['response' => $response]);
                throw new \RuntimeException('Invalid API response format for product detail');
            }

            $this->logger->info('Successfully fetched product detail', ['params' => $params, 'detail' => $response]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch product detail', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }
}