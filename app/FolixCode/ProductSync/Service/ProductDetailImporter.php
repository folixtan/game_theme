<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * 产品详情导入服务
 */
class ProductDetailImporter
{
    private VirtualGoodsApiService $apiService;
    private ProductRepositoryInterface $productRepository;
    private ProductImporter $productImporter;
    private LoggerInterface $logger;

    public function __construct(
        VirtualGoodsApiService $apiService,
        ProductRepositoryInterface $productRepository,
        ProductImporter $productImporter,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->productRepository = $productRepository;
        $this->productImporter = $productImporter;
        $this->logger = $logger;
    }

    /**
     * 导入产品详情
     *
     * @param string $productId
     * @return void
     */
    public function import(string $productId): void
    {
        try {
            if (empty($productId)) {
                throw new \InvalidArgumentException('Product ID is required');
            }

            $this->logger->info('Fetching product detail', ['product_id' => $productId]);

            // 从API获取产品详情
            $productDetail = $this->apiService->getProductDetail($productId);

            // 导入产品（使用ProductImporter）
            $this->productImporter->import($productDetail);

            $this->logger->info('Product detail imported successfully', ['product_id' => $productId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to import product detail', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}